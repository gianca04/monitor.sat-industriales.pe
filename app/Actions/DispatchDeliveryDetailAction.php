<?php

namespace App\Actions;

use App\Models\DeliveryDetail;
use App\Models\WarehouseLocation;
use App\Models\StockMovement;
use App\Services\InventoryService;
use App\Enums\DeliveryStatus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DispatchDeliveryDetailAction
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Dispatch a specific quantity of EPP variants for a given DeliveryDetail.
     *
     * @param DeliveryDetail $detail
     * @param WarehouseLocation $location
     * @param int $quantity
     * @throws InvalidArgumentException
     */
    public function execute(DeliveryDetail $detail, WarehouseLocation $location, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException("La cantidad a despachar debe ser mayor a cero.");
        }

        // 1. Calculate remaining quantity to fulfill
        $delivered = $detail->delivered_quantity;
        $remaining = $detail->quantity - $delivered;

        if ($quantity > $remaining) {
            throw new InvalidArgumentException("No se puede despachar más de la cantidad pendiente (Pendiente: {$remaining}).");
        }

        // 2. Validate stock availability
        $available = $this->inventoryService->checkStockAvailability($detail->epp_variant_id, $location->id, $quantity);

        if (!$available) {
            $stock = $this->inventoryService->getStock($detail->epp_variant_id, $location->id);
            $currentStock = $stock ? $stock->current_stock : 0;
            throw new InvalidArgumentException("Stock insuficiente en la ubicación seleccionada. Disponible: {$currentStock}, Requerido: {$quantity}.");
        }

        // 3. Execute transactional update
        DB::transaction(function () use ($detail, $location, $quantity, $delivered, $remaining) {
            // Find Stock record
            $stock = $this->inventoryService->getStock($detail->epp_variant_id, $location->id);
            
            // Subtract from current stock
            $stock->decrement('current_stock', $quantity);

            // Record stock movement
            StockMovement::create([
                'warehouse_id' => $location->warehouse_id,
                'warehouse_location_id' => $location->id,
                'epp_variant_id' => $detail->epp_variant_id,
                'delivery_detail_id' => $detail->id,
                'quantity' => $quantity,
                'type' => 'dispatch',
                'description' => "Despacho parcial de {$quantity} unidades para la entrega #{$detail->delivery_id}",
            ]);

            // Update DeliveryDetail status
            $newDeliveredTotal = $delivered + $quantity;
            if ($newDeliveredTotal >= $detail->quantity) {
                $detail->status = DeliveryStatus::DELIVERED;
            } else {
                $detail->status = DeliveryStatus::PARTIAL;
            }
            $detail->save();

            // Update parent Delivery status
            $this->updateParentDeliveryStatus($detail->delivery);
        });
    }

    /**
     * Dynamically determine and update the parent delivery status.
     */
    protected function updateParentDeliveryStatus($delivery): void
    {
        $details = $delivery->details()->get();

        $allDelivered = true;
        $anyDispatched = false;

        foreach ($details as $detail) {
            if ($detail->status !== DeliveryStatus::DELIVERED) {
                $allDelivered = false;
            }
            if ($detail->status === DeliveryStatus::DELIVERED || $detail->status === DeliveryStatus::PARTIAL) {
                $anyDispatched = true;
            }
        }

        if ($allDelivered) {
            $delivery->status = DeliveryStatus::DELIVERED;
        } elseif ($anyDispatched) {
            $delivery->status = DeliveryStatus::PARTIAL;
        } else {
            $delivery->status = DeliveryStatus::PENDING;
        }

        $delivery->save();
    }
}
