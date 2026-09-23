<?php

namespace Tests\Feature;

use App\Actions\CreateRequirementAction;
use App\Models\Category;
use App\Models\Client;
use App\Models\Item;
use App\Models\Requirement;
use App\Models\Subcategory;
use App\Models\SubClient;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateRequirementActionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected SubClient $subClient;

    protected Item $item1;

    protected Item $item2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $client = Client::factory()->create([
            'business_name' => 'SAT Industrial SAC',
            'person_type' => 'juridica',
        ]);

        $this->subClient = SubClient::create([
            'client_id' => $client->id,
            'name' => 'Sede Principal Lima',
            'address' => 'Av. Industrial 123',
        ]);

        $category = Category::create(['name' => 'Electricidad']);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Materiales Eléctricos',
        ]);
        $unit = Unit::create(['name' => 'Metro', 'symbol' => 'MTR']);

        $this->item1 = Item::create([
            'name' => 'Cable Vulcanizado 3x14 AWG',
            'sku' => 'CAB-001',
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'created_by' => $this->user->id,
        ]);

        $this->item2 = Item::create([
            'name' => 'Interruptor Termomagnético 2x20A',
            'sku' => 'INT-002',
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_action_creates_requirement_with_items_and_resolves_user(): void
    {
        $action = app(CreateRequirementAction::class);

        $requirementData = [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Instalación de luminarias LED en nave 2',
        ];

        $items = [
            ['item_id' => $this->item1->id, 'quantity' => 25.5],
            ['item_id' => $this->item2->id, 'quantity' => 4],
        ];

        $requirement = $action->execute($requirementData, $items, $this->user);

        $this->assertInstanceOf(Requirement::class, $requirement);
        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Instalación de luminarias LED en nave 2',
            'created_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item1->id,
            'quantity' => 25.50,
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item2->id,
            'quantity' => 4.00,
        ]);

        $this->assertEquals(2, $requirement->requirement_lists_count);
    }

    public function test_action_resolves_authenticated_user_when_user_not_passed(): void
    {
        $this->actingAs($this->user);

        $action = app(CreateRequirementAction::class);

        $requirement = $action->execute([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Mantenimiento Preventivo Tablero 01',
        ], [
            ['id' => $this->item1->id, 'quantity' => 10],
        ]);

        $this->assertEquals($this->user->id, $requirement->created_by);
    }

    public function test_web_endpoint_creates_requirement_with_session_auth(): void
    {
        $payload = [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Montaje de bandeja portacable',
            'items' => [
                ['id' => $this->item1->id, 'quantity' => 12.5],
                ['id' => $this->item2->id, 'quantity' => 3],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('requirements.web.store'), $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.sub_client_id', $this->subClient->id)
            ->assertJsonPath('data.activity_name', 'Montaje de bandeja portacable')
            ->assertJsonPath('data.creator.id', $this->user->id);

        $createdId = $response->json('data.id');
        $this->assertDatabaseHas('requirements', [
            'id' => $createdId,
            'created_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $createdId,
            'item_id' => $this->item1->id,
            'quantity' => 12.50,
        ]);
    }

    public function test_api_endpoint_creates_requirement_with_sanctum_auth(): void
    {
        Sanctum::actingAs($this->user);

        $payload = [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Reemplazo de seccionadores',
            'items' => [
                ['item_id' => $this->item2->id, 'quantity' => 6],
            ],
        ];

        $response = $this->postJson('/api/requirements', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.sub_client_id', $this->subClient->id)
            ->assertJsonPath('data.creator.id', $this->user->id);

        $createdId = $response->json('data.id');
        $this->assertDatabaseHas('requirements', ['id' => $createdId]);
    }

    public function test_endpoint_validates_required_fields(): void
    {
        $this->actingAs($this->user);

        // 1. Falta sub_client_id
        $response1 = $this->postJson(route('requirements.web.store'), [
            'activity_name' => 'Sin tienda',
        ]);
        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['sub_client_id']);

        // 2. Subcliente inexistente
        $response2 = $this->postJson(route('requirements.web.store'), [
            'sub_client_id' => 999999,
        ]);
        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['sub_client_id']);

        // 3. Ítem inexistente en la lista
        $response3 = $this->postJson(route('requirements.web.store'), [
            'sub_client_id' => $this->subClient->id,
            'items' => [
                ['item_id' => 999999, 'quantity' => 1],
            ],
        ]);
        $response3->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.item_id']);

        // 4. Cantidad inválida (negativa o cero)
        $response4 = $this->postJson(route('requirements.web.store'), [
            'sub_client_id' => $this->subClient->id,
            'items' => [
                ['item_id' => $this->item1->id, 'quantity' => 0],
            ],
        ]);
        $response4->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_action_rolls_back_transaction_if_item_fails(): void
    {
        $action = app(CreateRequirementAction::class);

        $initialRequirementsCount = Requirement::count();
        $initialRequirementListsCount = \App\Models\RequirementList::count();

        try {
            $action->execute(
                requirementData: [
                    'sub_client_id' => $this->subClient->id,
                    'activity_name' => 'Falla de prueba transaccional',
                ],
                items: [
                    ['item_id' => $this->item1->id, 'quantity' => 10],
                    ['item_id' => 99999999, 'quantity' => 5], // ítem inexistente viola FK
                ],
                user: $this->user
            );
            $this->fail('Se esperaba una excepción por clave foránea.');
        } catch (\Throwable $e) {
            // Transacción debe haber hecho rollback correctamente
        }

        $this->assertEquals($initialRequirementsCount, Requirement::count());
        $this->assertEquals($initialRequirementListsCount, \App\Models\RequirementList::count());
    }

    public function test_guest_cannot_access_requirement_endpoints(): void
    {
        $responseWeb = $this->postJson(route('requirements.web.store'), [
            'sub_client_id' => $this->subClient->id,
        ]);
        $responseWeb->assertStatus(401);
    }

    public function test_authenticated_user_can_update_requirement_via_web_endpoint(): void
    {
        $this->actingAs($this->user);

        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Actividad Original',
            'created_by' => $this->user->id,
        ]);

        $response = $this->putJson(route('requirements.web.update', $requirement), [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Actividad Actualizada',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.activity_name', 'Actividad Actualizada');

        $this->assertDatabaseHas('requirements', [
            'id' => $requirement->id,
            'activity_name' => 'Actividad Actualizada',
        ]);
    }

    public function test_authenticated_user_can_update_requirement_and_sync_items_via_web_endpoint(): void
    {
        $this->actingAs($this->user);

        $requirement = Requirement::create([
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Mantenimiento Preventivo',
            'created_by' => $this->user->id,
        ]);

        // Crear un ítem previo
        \App\Models\RequirementList::create([
            'requirement_id' => $requirement->id,
            'item_id' => $this->item1->id,
            'quantity' => 2,
        ]);

        // Actualizar: cambiamos cantidad del item1 y agregamos item2
        $response = $this->putJson(route('requirements.web.update', $requirement), [
            'sub_client_id' => $this->subClient->id,
            'activity_name' => 'Mantenimiento Correctivo',
            'items' => [
                ['item_id' => $this->item1->id, 'quantity' => 5],
                ['item_id' => $this->item2->id, 'quantity' => 10],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.activity_name', 'Mantenimiento Correctivo')
            ->assertJsonPath('data.items_count', 2);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item1->id,
            'quantity' => 5,
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item2->id,
            'quantity' => 10,
        ]);

        // Ahora actualizar vaciando item1 (solo queda item2)
        $response2 = $this->putJson(route('requirements.web.update', $requirement), [
            'sub_client_id' => $this->subClient->id,
            'items' => [
                ['item_id' => $this->item2->id, 'quantity' => 7],
            ],
        ]);

        $response2->assertStatus(200)
            ->assertJsonPath('data.items_count', 1);

        $this->assertDatabaseMissing('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item1->id,
        ]);

        $this->assertDatabaseHas('requirement_lists', [
            'requirement_id' => $requirement->id,
            'item_id' => $this->item2->id,
            'quantity' => 7,
        ]);
    }
}
