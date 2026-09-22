<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryAndSubcategoryApiTest extends TestCase
{
    use DatabaseTransactions;

    protected Category $category;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::firstOrCreate(
            ['email' => 'test_user_cat@example.com'],
            ['name' => 'Usuario Test', 'password' => bcrypt('password')]
        );

        $this->category = Category::firstOrCreate(
            ['name' => 'Categoría Test '.uniqid()],
            ['description' => 'Descripción de prueba']
        );
    }

    // ==========================================
    // PRUEBAS DE ENDPOINTS API (/api/...)
    // ==========================================

    public function test_can_list_categories_via_api(): void
    {
        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'description'],
                ],
            ]);
    }

    public function test_can_search_categories_via_api(): void
    {
        $uniqueName = 'CategoriaEspecial_'.uniqid();
        Category::create(['name' => $uniqueName, 'description' => 'Especial']);

        $response = $this->getJson('/api/categories?search='.$uniqueName);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $uniqueName]);
    }

    public function test_can_create_category_via_api(): void
    {
        $payload = [
            'name' => 'Ferretería '.uniqid(),
            'description' => 'Artículos de ferretería en general',
        ];

        $response = $this->postJson('/api/categories', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $payload['name']]);

        $this->assertDatabaseHas('categories', ['name' => $payload['name']]);
    }

    public function test_cannot_create_category_with_duplicate_name_via_api(): void
    {
        $payload = [
            'name' => $this->category->name,
            'description' => 'Intento duplicado',
        ];

        $response = $this->postJson('/api/categories', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_update_or_delete_category_via_api(): void
    {
        $updateResponse = $this->putJson('/api/categories/'.$this->category->id, [
            'name' => 'Nombre Modificado',
        ]);
        $updateResponse->assertNotFound();

        $deleteResponse = $this->deleteJson('/api/categories/'.$this->category->id);
        $deleteResponse->assertNotFound();
    }

    public function test_can_list_subcategories_via_api(): void
    {
        Subcategory::firstOrCreate(
            ['name' => 'Subcategoría Test '.uniqid()],
            ['category_id' => $this->category->id]
        );

        $response = $this->getJson('/api/subcategories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'category_id'],
                ],
            ]);
    }

    public function test_can_filter_subcategories_by_category_id_via_api(): void
    {
        $subcategory = Subcategory::create([
            'name' => 'Filtrado Sub_'.uniqid(),
            'category_id' => $this->category->id,
        ]);

        $otherCategory = Category::create(['name' => 'Otra Cat_'.uniqid()]);
        $otherSub = Subcategory::create([
            'name' => 'Otra Sub_'.uniqid(),
            'category_id' => $otherCategory->id,
        ]);

        $response = $this->getJson('/api/subcategories?category_id='.$this->category->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $subcategory->id])
            ->assertJsonMissing(['id' => $otherSub->id]);
    }

    public function test_can_create_subcategory_via_api(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name' => 'Subcategoría Nueva '.uniqid(),
        ];

        $response = $this->postJson('/api/subcategories', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $payload['name']]);

        $this->assertDatabaseHas('subcategories', ['name' => $payload['name']]);
    }

    public function test_cannot_create_subcategory_with_invalid_category_via_api(): void
    {
        $payload = [
            'category_id' => 99999999,
            'name' => 'Sub Invalida',
        ];

        $response = $this->postJson('/api/subcategories', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_cannot_update_or_delete_subcategory_via_api(): void
    {
        $subcategory = Subcategory::create([
            'name' => 'Sub Test Inmutable',
            'category_id' => $this->category->id,
        ]);

        $updateResponse = $this->putJson('/api/subcategories/'.$subcategory->id, [
            'name' => 'Sub Modificada',
        ]);
        $updateResponse->assertNotFound();

        $deleteResponse = $this->deleteJson('/api/subcategories/'.$subcategory->id);
        $deleteResponse->assertNotFound();
    }

    // ==========================================
    // PRUEBAS DE ENDPOINTS WEB (/categories, /subcategories)
    // ==========================================

    public function test_web_routes_require_authentication(): void
    {
        $this->getJson('/categories/search')->assertStatus(401);
        $this->postJson('/categories', ['name' => 'Cat'])->assertStatus(401);
        $this->getJson('/subcategories/search')->assertStatus(401);
        $this->postJson('/subcategories', ['name' => 'Sub'])->assertStatus(401);
    }

    public function test_can_list_and_search_categories_via_web_when_authenticated(): void
    {
        $uniqueName = 'CatWeb_'.uniqid();
        Category::create(['name' => $uniqueName]);

        $response = $this->actingAs($this->user)
            ->getJson('/categories/search?search='.$uniqueName);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $uniqueName]);
    }

    public function test_can_create_category_via_web_when_authenticated(): void
    {
        $payload = [
            'name' => 'CatWebCreada_'.uniqid(),
            'description' => 'Creada vía web endpoint',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/categories', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $payload['name']]);

        $this->assertDatabaseHas('categories', ['name' => $payload['name']]);
    }

    public function test_can_list_and_search_subcategories_via_web_when_authenticated(): void
    {
        $sub = Subcategory::create([
            'name' => 'SubWeb_'.uniqid(),
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/subcategories/search?search='.$sub->name);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $sub->name]);
    }

    public function test_can_create_subcategory_via_web_when_authenticated(): void
    {
        $payload = [
            'category_id' => $this->category->id,
            'name' => 'SubWebCreada_'.uniqid(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/subcategories', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => $payload['name']]);

        $this->assertDatabaseHas('subcategories', ['name' => $payload['name']]);
    }
}
