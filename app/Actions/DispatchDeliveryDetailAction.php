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
     * Dispatch EPP variants for a given DeliveryDetail from multiple locations.
     *
     * @param DeliveryDetail $detail
     * @param array $dispatches Array of arrays: [['warehouse_location_id' => X, 'quantity' => Y]]
     * @throws InvalidArgumentException
     */
    public function execute(DeliveryDetail $detail, array $dispatches): void
    {
        if (empty($dispatches)) {
            throw new InvalidArgumentException("Debe especificar al menos una ubicación para despachar.");
        }

        // 1. Calculate remaining quantity to fulfill
        $delivered = $detail->delivered_quantity;
        $remaining = $detail->quantity - $delivered;
        $totalQuantityToDispatch = collect($dispatches)->sum('quantity');

        if ($totalQuantityToDispatch <= 0) {
            throw new InvalidArgumentException("La cantidad total a despachar debe ser mayor a cero.");
        }

        if ($totalQuantityToDispatch > $remaining) {
            throw new InvalidArgumentException("No se puede despachar más de la cantidad pendiente (Pendiente: {$remaining}).");
        }

        // 2. Validate stock availability for each dispatch entry
        foreach ($dispatches as $dispatch) {
            $locationId = $dispatch['warehouse_location_id'] ?? null;
            $qty = (int) ($dispatch['quantity'] ?? 0);

            if (!$locationId || $qty <= 0) {
                throw new InvalidArgumentException("Cada distribución debe tener una ubicación válida y cantidad mayor a cero.");
            }

            $available = $this->inventoryService->checkStockAvailability($detail->epp_variant_id, $locationId, $qty);
            if (!$available) {
                $location = WarehouseLocation::findOrFail($locationId);
                $stock = $this->inventoryService->getStock($detail->epp_variant_id, $locationId);
                $currentStock = $stock ? $stock->current_stock : 0;
                throw new InvalidArgumentException("Stock insuficiente en la ubicación {$location->code}. Disponible: {$currentStock}, Requerido: {$qty}.");
            }
        }

        // 3. Execute transactional update
        DB::transaction(function () use ($detail, $dispatches, $delivered, $totalQuantityToDispatch) {
            $employeeName = $detail->employee ? "{$detail->employee->first_name} {$detail->employee->last_name}" : ($detail->delivery->employee ? "{$detail->delivery->employee->first_name} {$detail->delivery->employee->last_name}" : 'N/A');
            $subClientName = $detail->subClient?->name ?: ($detail->delivery->subClient?->name ?? 'N/A');

            foreach ($dispatches as $dispatch) {
                $locationId = $dispatch['warehouse_location_id'];
                $qty = (int) $dispatch['quantity'];

                $this->inventoryService->registerMovement(
                    $detail->epp_variant_id,
                    $locationId,
                    $qty,
                    'dispatch',
                    "Despacho de {$qty} unds. para Pedido #{$detail->delivery_id}. Colaborador: {$employeeName}. Tienda: {$subClientName}.",
                    $detail->id
                );
            }

            // Update DeliveryDetail status
            $newDeliveredTotal = $delivered + $totalQuantityToDispatch;
            if ($newDeliveredTotal >= $detail->quantity) {
                $detail->status = DeliveryStatus::DELIVERED;
                $detail->delivered_at = now();
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
