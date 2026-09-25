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
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RequirementListPaginationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Requirement $requirement;

    protected Category $categoryA;

    protected Category $categoryB;

    protected Subcategory $subcatA1;

    protected Subcategory $subcatB1;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->user = User::factory()->create();

        $client = Client::factory()->create(['person_type' => 'juridica']);
        $subClient = SubClient::factory()->create(['client_id' => $client->id]);

        $this->requirement = Requirement::create([
            'sub_client_id' => $subClient->id,
            'activity_name' => 'Instalación de Prueba',
            'created_by' => $this->user->id,
        ]);

        $this->categoryA = Category::create(['name' => 'Eléctricos']);
        $this->categoryB = Category::create(['name' => 'Ferretería']);

        $this->subcatA1 = Subcategory::create(['category_id' => $this->categoryA->id, 'name' => 'Cables']);
        $this->subcatB1 = Subcategory::create(['category_id' => $this->categoryB->id, 'name' => 'Tornillos']);

        $this->unit = Unit::create(['name' => 'Metros', 'symbol' => 'm']);
    }

    public function test_requirement_items_endpoint_returns_paginated_results(): void
    {
        // Crear 25 items asociados al requerimiento
        for ($i = 1; $i <= 25; $i++) {
            $item = Item::create([
                'name' => "Item Test {$i}",
                'sku' => "SKU-TST-{$i}",
                'subcategory_id' => $this->subcatA1->id,
                'unit_id' => $this->unit->id,
                'created_by' => $this->user->id,
            ]);

            RequirementList::create([
                'requirement_id' => $this->requirement->id,
                'item_id' => $item->id,
                'quantity' => $i,
            ]);
        }

        $response = $this->actingAs($this->user)
            ->getJson("/api/requirements/{$this->requirement->id}/items?per_page=10&page=1");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'requirement_id',
                        'item_id',
                        'quantity',
                        'item' => [
                            'id',
                            'name',
                            'sku',
                            'subcategory',
                        ],
                    ],
                ],
                'links',
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    public function test_requirement_items_endpoint_filters_by_category_and_subcategory(): void
    {
        $itemA = Item::create([
            'name' => 'Cable Vulcanizado',
            'sku' => 'CAB-001',
            'subcategory_id' => $this->subcatA1->id,
            'unit_id' => $this->unit->id,
        ]);

        $itemB = Item::create([
            'name' => 'Tornillo Autoperforante',
            'sku' => 'TOR-001',
            'subcategory_id' => $this->subcatB1->id,
            'unit_id' => $this->unit->id,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $itemA->id,
            'quantity' => 5,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $itemB->id,
            'quantity' => 10,
        ]);

        // Filtrar por categoría A
        $responseCat = $this->actingAs($this->user)
            ->getJson("/api/requirements/{$this->requirement->id}/items?category_id={$this->categoryA->id}");

        $responseCat->assertOk();
        $this->assertCount(1, $responseCat->json('data'));
        $this->assertEquals($itemA->id, $responseCat->json('data.0.item_id'));

        // Filtrar por subcategoría B1
        $responseSub = $this->actingAs($this->user)
            ->getJson("/api/requirements/{$this->requirement->id}/items?subcategory_id={$this->subcatB1->id}");

        $responseSub->assertOk();
        $this->assertCount(1, $responseSub->json('data'));
        $this->assertEquals($itemB->id, $responseSub->json('data.0.item_id'));
    }

    public function test_requirement_items_endpoint_filters_by_search(): void
    {
        $item1 = Item::create([
            'name' => 'Cinta Aislante 3M',
            'sku' => 'CIN-001',
            'subcategory_id' => $this->subcatA1->id,
        ]);

        $item2 = Item::create([
            'name' => 'Interruptor Termomagnético',
            'sku' => 'INT-001',
            'subcategory_id' => $this->subcatA1->id,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $item1->id,
            'quantity' => 2,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $item2->id,
            'quantity' => 1,
        ]);

        // Buscar por término "3M"
        $response = $this->actingAs($this->user)
            ->getJson("/api/requirements/{$this->requirement->id}/items?search=3M");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($item1->id, $response->json('data.0.item_id'));

        // Buscar por SKU "INT-"
        $responseSku = $this->actingAs($this->user)
            ->getJson("/api/requirements/{$this->requirement->id}/items?search=INT-");

        $responseSku->assertOk();
        $this->assertCount(1, $responseSku->json('data'));
        $this->assertEquals($item2->id, $responseSku->json('data.0.item_id'));
    }

    public function test_item_controller_index_filters_by_category_id(): void
    {
        Item::create([
            'name' => 'Cable Unipolar',
            'sku' => 'CAB-UNI',
            'subcategory_id' => $this->subcatA1->id,
        ]);

        Item::create([
            'name' => 'Perno Hexagonal',
            'sku' => 'PER-HEX',
            'subcategory_id' => $this->subcatB1->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/items?category_id={$this->categoryA->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('CAB-UNI', $response->json('data.0.sku'));
    }

    public function test_web_route_for_requirement_items_requires_authentication(): void
    {
        // Invitado es redirigido o recibe 401
        $responseGuest = $this->getJson("/requirements/{$this->requirement->id}/items");
        $responseGuest->assertUnauthorized();

        // Autenticado accede con éxito
        $responseAuth = $this->actingAs($this->user)
            ->getJson("/requirements/{$this->requirement->id}/items");
        $responseAuth->assertOk();
    }

    public function test_authenticated_user_can_clear_all_items_of_requirement(): void
    {
        $item = Item::create([
            'name' => 'Item Para Vaciar',
            'subcategory_id' => $this->subcatA1->id,
            'unit_id' => $this->unit->id,
        ]);

        RequirementList::create([
            'requirement_id' => $this->requirement->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        $this->assertGreaterThan(0, $this->requirement->requirementLists()->count());

        $response = $this->actingAs($this->user)
            ->deleteJson("/requirements/{$this->requirement->id}/items");

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertEquals(0, $this->requirement->requirementLists()->count());
    }

    public function test_authenticated_user_can_add_update_and_delete_item_in_requirement(): void
    {
        $item = Item::create([
            'name' => 'Item Individual',
            'subcategory_id' => $this->subcatA1->id,
            'unit_id' => $this->unit->id,
        ]);

        // 1. Agregar ítem en modo edición
        $storeResponse = $this->actingAs($this->user)
            ->postJson("/requirements/{$this->requirement->id}/items", [
                'item_id' => $item->id,
                'quantity' => 3,
            ]);

        $storeResponse->assertStatus(201)
            ->assertJsonPath('data.item_id', $item->id)
            ->assertJsonPath('data.quantity', 3);

        $listId = $storeResponse->json('data.id');

        // 2. Modificar cantidad
        $updateResponse = $this->actingAs($this->user)
            ->putJson("/requirements/{$this->requirement->id}/items/{$listId}", [
                'quantity' => 7.5,
            ]);

        $updateResponse->assertOk()
            ->assertJsonPath('data.quantity', 7.5);

        // 3. Eliminar ítem
        $deleteResponse = $this->actingAs($this->user)
            ->deleteJson("/requirements/{$this->requirement->id}/items/{$listId}");

        $deleteResponse->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('requirement_lists', [
            'id' => $listId,
        ]);
    }
}
