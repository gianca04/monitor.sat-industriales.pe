<?php

namespace Tests\Feature;

use App\Actions\BulkStockEntryAction;
use App\Models\Employee;
use App\Models\Epp;
use App\Models\EppVariant;
use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryCostingIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_full_inventory_flow_entry_delivery_and_costing_metrics(): void
    {
        // 1. Setup base master data
        $employee = Employee::factory()->create([
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
        ]);
        $warehouse = Warehouse::create(['name' => 'Almacén Principal']);
        $location = WarehouseLocation::create(['warehouse_id' => $warehouse->id, 'code' => 'SEC-01']);
        $epp = Epp::create(['name' => 'Botas Dieléctricas']);
        $variant = EppVariant::create([
            'epp_id' => $epp->id,
            'sku' => 'BOTA-T42',
            'variant_name' => 'Talla 42',
            'unit_cost' => 100.00,
        ]);

        // 2. Execute BulkStockEntryAction: Register entry of 20 boots at S/ 120.00 each
        $entryAction = app(BulkStockEntryAction::class);
        $entryAction->execute([
            [
                'epp_variant_id' => $variant->id,
                'warehouse_location_id' => $location->id,
                'quantity' => 20,
                'unit_cost' => 120.00,
                'description' => 'Compra Lote 2026-07',
            ],
        ]);

        // Assert Stock & Variant updated
        $stock = Stock::where('epp_variant_id', $variant->id)->where('warehouse_location_id', $location->id)->first();
        $this->assertNotNull($stock);
        $this->assertEquals(20, $stock->current_stock);
        $this->assertEquals(120.00, (float) $variant->fresh()->unit_cost);

        // 3. Create Delivery and process dispatch via InventoryService
        $delivery = Delivery::create(['code' => 'DEL-2026-001', 'delivery_date' => now()]);
        $detail = DeliveryDetail::create([
            'delivery_id' => $delivery->id,
            'epp_variant_id' => $variant->id,
            'employee_id' => $employee->id,
            'quantity' => 3,
        ]);

        $inventoryService = app(InventoryService::class);
        $movement = $inventoryService->registerMovement(
            eppVariantId: $variant->id,
            warehouseLocationId: $location->id,
            quantity: 3,
            type: 'dispatch',
            description: 'Entrega de botas a trabajador',
            deliveryDetailId: $detail->id
        );

        // Assert movement and detail took unit_cost (120.00)
        $this->assertEquals(120.00, (float) $movement->unit_cost);
        $this->assertEquals(120.00, (float) $detail->fresh()->unit_cost);
        $this->assertEquals(17, $stock->fresh()->current_stock); // 20 - 3 = 17

        // 4. METRIC 1: How much in EPPs was delivered to Carlos Mendoza?
        $deliveredCostToEmployee = DeliveryDetail::where('employee_id', $employee->id)
            ->selectRaw('SUM(quantity * unit_cost) as total_cost')
            ->value('total_cost');

        $this->assertEquals(360.00, (float) $deliveredCostToEmployee); // 3 * S/ 120.00

        // 5. METRIC 2: Total EPP entries registered this month
        $monthlyEntryTotalValue = StockMovement::where('type', 'input')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('SUM(quantity * unit_cost) as total_value')
            ->value('total_value');

        $this->assertEquals(2400.00, (float) $monthlyEntryTotalValue); // 20 * S/ 120.00
    }
}
