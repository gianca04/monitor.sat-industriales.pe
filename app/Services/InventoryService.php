<?php

namespace App\Services;

use App\Models\Stock;

class InventoryService
{
    /**
     * Get the stock record for a given EPP variant and warehouse location.
     */
    public function getStock(int $eppVariantId, int $warehouseLocationId): ?Stock
    {
        return Stock::where('epp_variant_id', $eppVariantId)
            ->where('warehouse_location_id', $warehouseLocationId)
            ->first();
    }

    /**
     * Check if there is enough stock available for a given EPP variant and warehouse location.
     */
    public function checkStockAvailability(int $eppVariantId, int $warehouseLocationId, int $requiredQuantity): bool
    {
        $stock = $this->getStock($eppVariantId, $warehouseLocationId);

        if (!$stock) {
            return false;
        }

        return $stock->current_stock >= $requiredQuantity;
    }

    /**
     * Check if the given EPP or EPP Variant's current stock is below or equal to its minimum stock.
     */
    public function isBelowMinimum(\App\Models\Epp|\App\Models\EppVariant $item): bool
    {
        return $item->is_below_minimum;
    }

    /**
     * Check if the given EPP requires replenishment.
     */
    public function requiresReplenishment(\App\Models\Epp $epp): bool
    {
        return $epp->requires_replenishment;
    }

    /**
     * Register a stock movement and adjust the stock quantity.
     */
    public function registerMovement(
        int $eppVariantId,
        int $warehouseLocationId,
        int $quantity,
        string $type,
        ?string $description = null,
        ?int $deliveryDetailId = null
    ): \App\Models\StockMovement {
        $location = \App\Models\WarehouseLocation::findOrFail($warehouseLocationId);
        
        $stock = Stock::firstOrCreate(
            [
                'warehouse_location_id' => $warehouseLocationId,
                'epp_variant_id' => $eppVariantId,
            ],
            [
                'warehouse_id' => $location->warehouse_id,
                'current_stock' => 0,
                'minimum_stock' => 0,
                'maximum_stock' => 0,
            ]
        );

        $isNegative = in_array($type, ['loss', 'adjustment_out', 'transfer_out', 'dispatch', 'output']);
        
        if ($isNegative) {
            $stock->decrement('current_stock', $quantity);
        } else {
            $stock->increment('current_stock', $quantity);
        }

        return \App\Models\StockMovement::create([
            'warehouse_id' => $location->warehouse_id,
            'warehouse_location_id' => $warehouseLocationId,
            'epp_variant_id' => $eppVariantId,
            'delivery_detail_id' => $deliveryDetailId,
            'quantity' => $quantity,
            'type' => $type,
            'description' => $description,
        ]);
    }
}

