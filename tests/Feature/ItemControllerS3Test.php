<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemControllerS3Test extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Subcategory $subcategory;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $category = Category::create(['name' => 'Ferretería Industrial']);
        $this->subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Herramientas Eléctricas',
        ]);
        $this->unit = Unit::create([
            'name' => 'Unidad',
            'symbol' => 'UND',
        ]);
    }

    public function test_store_item_with_photo_uploads_to_s3_as_webp(): void
    {
        $photo = UploadedFile::fake()->image('taladro.png', 400, 400);

        $response = $this->postJson(route('items.store'), [
            'name' => 'Taladro Percutor 750W',
            'sku' => 'TAL-001',
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
            'photo' => $photo,
        ]);

        $response->assertStatus(201);

        $itemId = $response->json('data.id');
        $item = Item::findOrFail($itemId);

        $this->assertNotEmpty($item->photo);
        $this->assertStringStartsWith('items/', $item->photo);
        $this->assertStringEndsWith('.webp', $item->photo);

        // Verificar existencia en disco S3
        Storage::disk('s3')->assertExists($item->photo);

        // Verificar que la URL en el recurso apunta al endpoint S3
        $this->assertNotNull($response->json('data.photo'));
        $this->assertStringContainsString($item->photo, $response->json('data.photo'));
    }

    public function test_update_item_replaces_old_photo_in_s3(): void
    {
        // 1. Crear ítem con foto previa
        $oldPhoto = UploadedFile::fake()->image('antigua.jpg', 300, 300);
        $createResponse = $this->postJson(route('items.store'), [
            'name' => 'Amoladora Angular 4.5',
            'sku' => 'AMO-001',
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
            'photo' => $oldPhoto,
        ]);

        $item = Item::findOrFail($createResponse->json('data.id'));
        $oldPhotoPath = $item->photo;
        Storage::disk('s3')->assertExists($oldPhotoPath);

        // 2. Actualizar con nueva foto
        $newPhoto = UploadedFile::fake()->image('nueva.png', 500, 500);
        $updateResponse = $this->putJson("/api/items/{$item->id}", [
            'name' => 'Amoladora Angular 4.5 Pro',
            'photo' => $newPhoto,
        ]);

        $updateResponse->assertStatus(200);

        $item->refresh();
        $this->assertNotEquals($oldPhotoPath, $item->photo);
        Storage::disk('s3')->assertMissing($oldPhotoPath);
        Storage::disk('s3')->assertExists($item->photo);
    }

    public function test_destroy_item_removes_photo_from_s3(): void
    {
        $photo = UploadedFile::fake()->image('sierra.jpg', 200, 200);
        $createResponse = $this->postJson(route('items.store'), [
            'name' => 'Sierra Circular 7-1/4',
            'sku' => 'SIE-001',
            'subcategory_id' => $this->subcategory->id,
            'unit_id' => $this->unit->id,
            'photo' => $photo,
        ]);

        $item = Item::findOrFail($createResponse->json('data.id'));
        $photoPath = $item->photo;
        Storage::disk('s3')->assertExists($photoPath);

        // Eliminar ítem
        $deleteResponse = $this->deleteJson("/api/items/{$item->id}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('items', ['id' => $item->id]);
        Storage::disk('s3')->assertMissing($photoPath);
    }
}
