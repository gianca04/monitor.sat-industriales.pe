<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Requirement;
use App\Models\RequirementList;
use App\Models\Subcategory;
use App\Models\SubClient;
use App\Models\Unit;
use App\Models\User;
use App\Services\RequirementExportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class RequirementExportServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected Requirement $requirement;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::first() ?? User::factory()->create();

        $client = Client::first() ?? Client::create([
            'business_name' => 'Cliente Export Test',
            'document_number' => '20000000000',
        ]);

        $subClient = SubClient::first() ?? SubClient::create([
            'name' => 'Sede Export Test',
            'client_id' => $client->id,
        ]);

        $this->requirement = Requirement::create([
            'sub_client_id' => $subClient->id,
            'activity_name' => 'Actividad de Prueba',
            'created_by' => $user->id,
        ]);

        $unit = Unit::firstOrCreate(['name' => 'Unidad Test', 'symbol' => 'UND']);

        $category = Category::firstOrCreate(['name' => 'Categoría Test Export']);
        $subcategory = Subcategory::firstOrCreate(['name' => 'Sub Test', 'category_id' => $category->id]);

        $item1 = Item::create([
            'sku' => 'SKU-001',
            'name' => 'Item Test 1',
            'unit_id' => $unit->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $user->id,
            // 'photo' => omitimos para test rápido sin conexión
        ]);

        $item2 = Item::create([
            'sku' => 'SKU-002',
            'name' => 'Item Test 2',
            'unit_id' => $unit->id,
            'subcategory_id' => $subcategory->id,
            'created_by' => $user->id,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $item1->id,
            'quantity' => 10,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $item2->id,
            'quantity' => 5,
        ]);
    }

    public function test_it_can_export_specific_list_of_items()
    {
        $service = new RequirementExportService;

        // Exportamos solo 1 item (el primero)
        $specificItems = $this->requirement->requirementLists()->take(1)->get();

        $filePath = $service->export($this->requirement, $specificItems);

        $this->assertFileExists($filePath);

        // Verificamos el contenido del Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // A1 debe tener info del cliente
        $this->assertStringContainsString($this->requirement->subClient->name, $sheet->getCell('A1')->getValue());
        $this->assertStringContainsString('Actividad de Prueba', $sheet->getCell('A1')->getValue());

        // Fila 4 debe tener el primer item
        $this->assertEquals('Item Test 1', $sheet->getCell('A4')->getValue());

        // La fila 5 debe estar vacía porque solo pasamos 1 item a la exportación
        $this->assertEmpty($sheet->getCell('A5')->getValue());

        // Cleanup
        unlink($filePath);
    }

    public function test_it_can_export_all_items()
    {
        $service = new RequirementExportService;

        // Usamos la segunda opción: exportar todos
        $filePath = $service->exportAll($this->requirement);

        $this->assertFileExists($filePath);

        // Verificamos el contenido del Excel
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Fila 4 debe tener el primer item
        $this->assertEquals('Item Test 1', $sheet->getCell('A4')->getValue());

        // Fila 5 debe tener el segundo item
        $this->assertEquals('Item Test 2', $sheet->getCell('A5')->getValue());

        // Cleanup
        unlink($filePath);
    }
}
