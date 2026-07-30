<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\Epp;
use App\Models\EppVariant;
use App\Models\Delivery;
use App\Models\DeliveryDetail;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryCostingUnitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_calculates_total_delivered_cost_for_a_specific_employee(): void
    {
        // Arrange
        $employee = Employee::factory()->create(['first_name' => 'Juan', 'last_name' => 'Pérez']);
        $epp = Epp::create(['name' => 'Casco de Seguridad']);
        $variant1 = EppVariant::create(['epp_id' => $epp->id, 'sku' => 'CASCO-RED', 'unit_cost' => 50.00]);
        $variant2 = EppVariant::create(['epp_id' => $epp->id, 'sku' => 'CASCO-BLUE', 'unit_cost' => 80.00]);

        $delivery = Delivery::create(['code' => 'DEL-001', 'delivery_date' => now()]);

        // Deliver 2 red helmets at S/ 50.00 each = S/ 100.00
        DeliveryDetail::create([
            'delivery_id' => $delivery->id,
            'epp_variant_id' => $variant1->id,
            'employee_id' => $employee->id,
            'quantity' => 2,
            'unit_cost' => 50.00,
        ]);

        // Deliver 1 blue helmet at S/ 80.00 each = S/ 80.00
        DeliveryDetail::create([
            'delivery_id' => $delivery->id,
            'epp_variant_id' => $variant2->id,
            'employee_id' => $employee->id,
            'quantity' => 1,
            'unit_cost' => 80.00,
        ]);

        // Act: Calculate total delivered value to Juan Pérez
        $totalDeliveredCost = DeliveryDetail::where('employee_id', $employee->id)
            ->selectRaw('SUM(quantity * unit_cost) as total_cost')
            ->value('total_cost');

        $totalDeliveredQuantity = DeliveryDetail::where('employee_id', $employee->id)
            ->sum('quantity');

        // Assert
        $this->assertEquals(3, $totalDeliveredQuantity);
        $this->assertEquals(180.00, (float) $totalDeliveredCost);
    }

    public function test_calculates_total_epp_entries_value_registered_this_month(): void
    {
        // Arrange
        $warehouse = Warehouse::create(['name' => 'Almacén Central']);
        $location = WarehouseLocation::create(['warehouse_id' => $warehouse->id, 'code' => 'LOC-A1']);
        $epp = Epp::create(['name' => 'Guantes Nitrilo']);
        $variant = EppVariant::create(['epp_id' => $epp->id, 'sku' => 'GUANTE-M', 'unit_cost' => 10.00]);

        // Entry 1: Current month, 10 units at S/ 12.00 = S/ 120.00
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'epp_variant_id' => $variant->id,
            'quantity' => 10,
            'unit_cost' => 12.00,
            'type' => 'input',
            'created_at' => now(),
        ]);

        // Entry 2: Current month, 5 units at S/ 15.00 = S/ 75.00
        StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'epp_variant_id' => $variant->id,
            'quantity' => 5,
            'unit_cost' => 15.00,
            'type' => 'input',
            'created_at' => now(),
        ]);

        // Entry 3: Previous month (should be excluded from current month query)
        $pastMovement = StockMovement::create([
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'epp_variant_id' => $variant->id,
            'quantity' => 20,
            'unit_cost' => 10.00,
            'type' => 'input',
        ]);
        $pastMovement->created_at = now()->subMonth();
        $pastMovement->save();

        // Act: Query total EPP entry value for current month (scoped to test variant)
        $monthlyEntryTotalCost = StockMovement::where('epp_variant_id', $variant->id)
            ->where('type', 'input')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->selectRaw('SUM(quantity * unit_cost) as total')
            ->value('total');

        $monthlyEntryTotalQuantity = StockMovement::where('epp_variant_id', $variant->id)
            ->where('type', 'input')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('quantity');

        // Assert
        $this->assertEquals(15, $monthlyEntryTotalQuantity);
        $this->assertEquals(195.00, (float) $monthlyEntryTotalCost);
    }
}
