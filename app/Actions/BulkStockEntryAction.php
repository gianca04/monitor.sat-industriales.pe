<?php

namespace App\Actions;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\WarehouseLocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BulkStockEntryAction
{
    /**
     * Process bulk stock entries.
     *
     * @param array $entries Array of arrays: [['epp_variant_id' => X, 'warehouse_location_id' => Y, 'quantity' => Z, 'description' => '...']]
     */
    public function execute(array $entries): void
    {
        DB::transaction(function () use ($entries) {
            foreach ($entries as $entry) {
                $variantId = $entry['epp_variant_id'] ?? null;
                $locationId = $entry['warehouse_location_id'] ?? null;
                $quantity = $entry['quantity'] ?? 0;
                $description = $entry['description'] ?? 'Ingreso masivo de stock';

                if (!$variantId || !$locationId) {
                    throw new InvalidArgumentException("Cada entrada debe especificar 'epp_variant_id' y 'warehouse_location_id'.");
                }

                if ($quantity <= 0) {
                    throw new InvalidArgumentException("La cantidad para cada entrada de stock debe ser mayor a cero.");
                }

                // Get the warehouse location to resolve warehouse_id
                $location = WarehouseLocation::findOrFail($locationId);

                // Find or create Stock
                $stock = Stock::firstOrCreate(
                    [
                        'warehouse_location_id' => $locationId,
                        'epp_variant_id' => $variantId,
                    ],
                    [
                        'warehouse_id' => $location->warehouse_id,
                        'current_stock' => 0,
                        'minimum_stock' => 0,
                        'maximum_stock' => 0,
                    ]
                );

                // Increment stock
                $stock->increment('current_stock', $quantity);

                // Record movement
                StockMovement::create([
                    'warehouse_id' => $location->warehouse_id,
                    'warehouse_location_id' => $locationId,
                    'epp_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'type' => 'input',
                    'description' => $description,
                ]);
            }
        });
    }
}
