<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\WarehouseLocation;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferStockAction
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Transfer stock from one warehouse location to another.
     *
     * @param int $eppVariantId
     * @param int $sourceLocationId
     * @param int $targetLocationId
     * @param int $quantity
     * @param string|null $description
     * @throws InvalidArgumentException
     */
    public function execute(
        int $eppVariantId,
        int $sourceLocationId,
        int $targetLocationId,
        int $quantity,
        ?string $description = null
    ): void {
        if ($sourceLocationId === $targetLocationId) {
            throw new InvalidArgumentException("La ubicación de origen y destino no pueden ser la misma.");
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad a transferir debe ser mayor a cero.");
        }

        // Validate stock in source location
        $available = $this->inventoryService->checkStockAvailability($eppVariantId, $sourceLocationId, $quantity);

        if (!$available) {
            $stock = $this->inventoryService->getStock($eppVariantId, $sourceLocationId);
            $currentStock = $stock ? $stock->current_stock : 0;
            throw new InvalidArgumentException("Stock insuficiente en la ubicación de origen. Disponible: {$currentStock}, Requerido: {$quantity}.");
        }

        $sourceLocation = WarehouseLocation::findOrFail($sourceLocationId);
        $targetLocation = WarehouseLocation::findOrFail($targetLocationId);

        DB::transaction(function () use ($eppVariantId, $sourceLocation, $targetLocation, $quantity, $description) {
            $descText = $description ?: "Transferencia de stock de {$sourceLocation->code} a {$targetLocation->code}";

            // 1. Decrement source stock
            $sourceStock = $this->inventoryService->getStock($eppVariantId, $sourceLocation->id);
            $sourceStock->decrement('current_stock', $quantity);

            // 2. Increment target stock (or create it first)
            $targetStock = Stock::firstOrCreate(
                [
                    'warehouse_location_id' => $targetLocation->id,
                    'epp_variant_id' => $eppVariantId,
                ],
                [
                    'warehouse_id' => $targetLocation->warehouse_id,
                    'current_stock' => 0,
                    'minimum_stock' => 0,
                    'maximum_stock' => 0,
                ]
            );
            $targetStock->increment('current_stock', $quantity);

            // 3. Record stock movement: transfer_out for source
            StockMovement::create([
                'warehouse_id' => $sourceLocation->warehouse_id,
                'warehouse_location_id' => $sourceLocation->id,
                'epp_variant_id' => $eppVariantId,
                'quantity' => $quantity,
                'type' => 'transfer_out',
                'description' => "Salida por transferencia: {$descText}",
            ]);

            // 4. Record stock movement: transfer_in for target
            StockMovement::create([
                'warehouse_id' => $targetLocation->warehouse_id,
                'warehouse_location_id' => $targetLocation->id,
                'epp_variant_id' => $eppVariantId,
                'quantity' => $quantity,
                'type' => 'transfer_in',
                'description' => "Ingreso por transferencia: {$descText}",
            ]);
        });
    }
}
