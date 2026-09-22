<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Requirement;
use App\Models\RequirementList;
use App\Models\Subcategory;
use App\Models\SubClient;
use App\Models\Unit;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InventoryAndRequirementApiTest extends TestCase
{
    use DatabaseTransactions;

    protected Unit $unit;

    protected Subcategory $subcategory;

    protected SubClient $subClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->unit = Unit::firstOrCreate(
            ['name' => 'Metro'],
            ['symbol' => 'm']
        );

        $category = Category::firstOrCreate(['name' => 'Categoría Test']);
        $this->subcategory = Subcategory::firstOrCreate(
            ['name' => 'Cables Test'],
            ['category_id' => $category->id]
        );

        $client = Client::first() ?? Client::create([
            'business_name' => 'Cliente Test',
            'document_number' => '20123456789',
        ]);

        $this->subClient = SubClient::first() ?? SubClient::create([
            'name' => 'Sede Principal Test',
            'client_id' => $client->id,
        ]);
    }

    // --- UNITS ---

    public function test_can_list_units(): void
    {
        $response = $this->getJson('/api/units');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'symbol', 'created_at', 'updated_at'],
                ],
            ]);
    }

    public function test_can_create_unit(): void
    {
        $payload = [
            'name' => 'Kilogramo Test '.uniqid(),
            'symbol' => 'kg',
        ];

        $response = $this->postJson('/api/units', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => $payload['name'],
                'symbol' => 'kg',
            ]);

        $this->assertDatabaseHas('units', ['name' => $payload['name']]);
    }

    public function test_cannot_create_duplicate_unit_name(): void
    {
        $payload = [
            'name' => $this->unit->name,
            'symbol' => 'm',
        ];

        $response = $this->postJson('/api/units', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_show_unit(): void
    {
        $response = $this->getJson("/api/units/{$this->unit->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $this->unit->id, 'name' => $this->unit->name]);
    }

    public function test_can_update_unit(): void
    {
        $payload = [
            'name' => 'Metro Modificado '.uniqid(),
            'symbol' => 'mt',
        ];

        $response = $this->putJson("/api/units/{$this->unit->id}", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['symbol' => 'mt']);

        $this->assertDatabaseHas('units', ['id' => $this->unit->id, 'symbol' => 'mt']);
    }

    public function test_can_delete_unit_without_items(): void
    {
        $unit = Unit::create(['name' => 'Galón '.uniqid(), 'symbol' => 'gl']);

        $response = $this->deleteJson("/api/units/{$unit->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    // --- ITEMS ---

    public function test_can_list_items(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_item_with_auto_generated_sku(): void
    {
        $payload = [
            'name' => 'Cable UTP Cat6 '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $payload['name']]);

        $createdItem = Item::where('name', $payload['name'])->first();
        $this->assertNotNull($createdItem);
        $this->assertNotEmpty($createdItem->sku);
    }

    public function test_can_create_item_with_custom_sku(): void
    {
        $customSku = 'CUSTOM-SKU-'.uniqid();
        $payload = [
            'sku' => $customSku,
            'name' => 'Tubo Conduit '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ];

        $response = $this->postJson('/api/items', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['sku' => $customSku]);

        $this->assertDatabaseHas('items', ['sku' => $customSku]);
    }

    public function test_can_show_item(): void
    {
        $item = Item::create([
            'name' => 'Item Para Mostrar '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->getJson("/api/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $item->id, 'name' => $item->name]);
    }

    public function test_can_update_item(): void
    {
        $item = Item::create([
            'name' => 'Item Original '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $newName = 'Item Actualizado '.uniqid();
        $response = $this->putJson("/api/items/{$item->id}", [
            'name' => $newName,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $newName]);

        $this->assertDatabaseHas('items', ['id' => $item->id, 'name' => $newName]);
    }

    public function test_can_delete_item(): void
    {
        $item = Item::create([
            'name' => 'Item Para Borrar '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->deleteJson("/api/items/{$item->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
    }

    // --- REQUIREMENTS ---

    public function test_can_list_requirements(): void
    {
        $response = $this->getJson('/api/requirements');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_can_create_requirement_with_items_in_single_payload(): void
    {
        $item1 = Item::create([
            'name' => 'Material 1 '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $item2 = Item::create([
            'name' => 'Material 2 '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $payload = [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Instalación Eléctrica Sala 3',
            'items' => [
                ['item_id' => $item1->id, 'quantity' => 10],
                ['item_id' => $item2->id, 'quantity' => 25.5],
            ],
        ];

        $response = $this->postJson('/api/requirements', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['activity_name' => 'Instalación Eléctrica Sala 3']);

        $reqId = $response->json('data.id');
        $this->assertDatabaseHas('requirements', [
            'id' => $reqId,
            'activity_name' => 'Instalación Eléctrica Sala 3',
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $reqId,
            'item_id' => $item1->id,
            'quantity' => 10,
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $reqId,
            'item_id' => $item2->id,
            'quantity' => 25.5,
        ]);
    }

    public function test_can_show_requirement(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Mantenimiento Preventivo',
        ]);

        $response = $this->getJson("/api/requirements/{$requirement->id}");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $requirement->id,
                'activity_name' => 'Mantenimiento Preventivo',
            ]);
    }

    public function test_can_update_requirement(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Actividad Original',
        ]);

        $response = $this->putJson("/api/requirements/{$requirement->id}", [
            'activity_name' => 'Actividad Corregida',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['activity_name' => 'Actividad Corregida']);

        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'activity_name' => 'Actividad Corregida',
        ]);
    }

    public function test_can_delete_requirement(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Para Eliminar',
        ]);

        $response = $this->deleteJson("/api/requirements/{$requirement->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('requirements', ['id' => $requirement->id]);
    }

    // --- REQUIREMENT ITEMS ---

    public function test_can_add_item_to_requirement_via_nested_endpoint(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Req Nested',
        ]);

        $item = Item::create([
            'name' => 'Item Anidado '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = $this->postJson("/api/requirements/{$requirement->id}/items", [
            'item_id' => $item->id,
            'quantity' => 15,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['quantity' => 15]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $item->id,
            'quantity' => 15,
        ]);
    }

    public function test_adding_existing_item_increments_quantity(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Req Increment',
        ]);

        $item = Item::create([
            'name' => 'Item Incrementable '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        // Primera inserción: 5
        $this->postJson("/api/requirements/{$requirement->id}/items", [
            'item_id' => $item->id,
            'quantity' => 5,
        ])->assertStatus(201);

        // Segunda inserción: +10
        $response = $this->postJson("/api/requirements/{$requirement->id}/items", [
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => 15]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $item->id,
            'quantity' => 15,
        ]);
    }

    public function test_can_update_requirement_item_quantity(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Req Update Item',
        ]);

        $item = Item::create([
            'name' => 'Item Por Actualizar '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $reqList = RequirementList::create([
            'requirement_id' => $requirement->id,
            'item_id' => $item->id,
            'quantity' => 4,
        ]);

        $response = $this->putJson("/api/requirements/{$requirement->id}/items/{$reqList->id}", [
            'quantity' => 8.5,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['quantity' => 8.5]);

        $this->assertDatabaseHas('requirement_lists', [
            'id' => $reqList->id,
            'quantity' => 8.5,
        ]);
    }

    public function test_can_delete_requirement_item(): void
    {
        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Req Delete Item',
        ]);

        $item = Item::create([
            'name' => 'Item Por Quitar '.uniqid(),
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
        ]);

        $reqList = RequirementList::create([
            'requirement_id' => $requirement->id,
            'item_id' => $item->id,
            'quantity' => 2,
        ]);

        $response = $this->deleteJson("/api/requirements/{$requirement->id}/items/{$reqList->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('requirement_lists', ['id' => $reqList->id]);
    }
}
