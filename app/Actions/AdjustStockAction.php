<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\WarehouseLocation;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AdjustStockAction
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Adjust stock for a warehouse location (loss/merma).
     *
     * @param int $eppVariantId
     * @param int $locationId
     * @param int $quantity
     * @param string $type
     * @param string|null $description
     * @throws InvalidArgumentException
     */
    public function execute(
        int $eppVariantId,
        int $locationId,
        int $quantity,
        string $type,
        ?string $description = null
    ): void {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad debe ser mayor a cero.");
        }

        if (!in_array($type, ['loss', 'adjustment_out'])) {
            throw new InvalidArgumentException("El tipo de ajuste no es válido.");
        }

        // Validate stock
        $available = $this->inventoryService->checkStockAvailability($eppVariantId, $locationId, $quantity);

        if (!$available) {
            $stock = $this->inventoryService->getStock($eppVariantId, $locationId);
            $currentStock = $stock ? $stock->current_stock : 0;
            throw new InvalidArgumentException("Stock insuficiente para realizar el ajuste. Disponible: {$currentStock}, Requerido: {$quantity}.");
        }

        $location = WarehouseLocation::findOrFail($locationId);

        DB::transaction(function () use ($eppVariantId, $location, $quantity, $type, $description) {
            $descText = $description ?: "Ajuste de inventario (Merma)";

            // 1. Decrement stock
            $stock = $this->inventoryService->getStock($eppVariantId, $location->id);
            $stock->decrement('current_stock', $quantity);

            // 2. Record stock movement
            StockMovement::create([
                'warehouse_id' => $location->warehouse_id,
                'warehouse_location_id' => $location->id,
                'epp_variant_id' => $eppVariantId,
                'quantity' => $quantity,
                'type' => $type,
                'description' => $descText,
            ]);
        });
    }
}
