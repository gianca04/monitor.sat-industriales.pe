<?php

namespace Tests\Feature;

use App\Actions\UploadItemPhotoAction;
use App\Jobs\UploadItemPhotoJob;
use App\Models\Category;
use App\Models\Item;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemPhotoQueueTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        Storage::fake('local');

        $this->user = User::factory()->create();

        $category = Category::create(['name' => 'Ferretería Industrial']);
        $subcategory = Subcategory::create([
            'category_id' => $category->id,
            'name' => 'Herramientas Eléctricas',
        ]);
        $unit = Unit::create([
            'name' => 'Unidad',
            'symbol' => 'UND',
        ]);

        $this->item = Item::create([
            'name' => 'Esmeril Angular 9 Pulgadas',
            'sku' => 'ESM-00999',
            'subcategory_id' => $subcategory->id,
            'unit_id' => $unit->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_guest_cannot_access_photo_queue_endpoint(): void
    {
        $response = $this->postJson("/items/{$this->item->id}/photo/queue", [
            'photo' => UploadedFile::fake()->image('foto.jpg'),
        ]);

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_queue_photo_upload_via_route_binding(): void
    {
        Queue::fake();

        $photo = UploadedFile::fake()->image('esmeril.png', 600, 600);

        $response = $this->actingAs($this->user)
            ->postJson("/items/{$this->item->id}/photo/queue", [
                'photo' => $photo,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_id' => $this->item->id,
                    'status' => 'queued',
                ],
            ]);

        Queue::assertPushed(UploadItemPhotoJob::class, function (UploadItemPhotoJob $job) {
            return $job->item->id === $this->item->id
                && Storage::disk('local')->exists($job->tempPath);
        });
    }

    public function test_authenticated_user_can_queue_photo_upload_via_general_route(): void
    {
        Queue::fake();

        $photo = UploadedFile::fake()->image('general.jpg', 400, 400);

        $response = $this->actingAs($this->user)
            ->postJson('/items/photo/queue', [
                'item_id' => $this->item->id,
                'photo' => $photo,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_id' => $this->item->id,
                    'status' => 'queued',
                ],
            ]);

        Queue::assertPushed(UploadItemPhotoJob::class, function (UploadItemPhotoJob $job) {
            return $job->item->id === $this->item->id;
        });
    }

    public function test_photo_queue_endpoint_validates_image_file(): void
    {
        // 1. Sin archivo
        $response = $this->actingAs($this->user)
            ->postJson("/items/{$this->item->id}/photo/queue", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        // 2. Archivo no válido (texto en vez de imagen)
        $invalidFile = UploadedFile::fake()->create('documento.txt', 100);

        $response2 = $this->actingAs($this->user)
            ->postJson("/items/{$this->item->id}/photo/queue", [
                'photo' => $invalidFile,
            ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);

        // 3. Ítem inexistente en ruta general
        $response3 = $this->actingAs($this->user)
            ->postJson('/items/photo/queue', [
                'item_id' => 999999,
                'photo' => UploadedFile::fake()->image('foto.png'),
            ]);

        $response3->assertStatus(422)
            ->assertJsonValidationErrors(['item_id']);
    }

    public function test_upload_item_photo_job_executes_action_and_updates_item(): void
    {
        // Guardar archivo temporal en disco local
        $tempPath = 'temp/item-photos/test-job.png';
        $fakeImage = UploadedFile::fake()->image('temp-esmeril.png', 300, 300);
        Storage::disk('local')->put($tempPath, file_get_contents($fakeImage->getRealPath()));

        $this->assertTrue(Storage::disk('local')->exists($tempPath));

        // Ejecutar el Job
        $job = new UploadItemPhotoJob($this->item, $tempPath);
        $job->handle(app(UploadItemPhotoAction::class));

        // Verificar que el ítem tiene foto guardada en S3
        $this->item->refresh();
        $this->assertNotEmpty($this->item->photo);
        $this->assertStringStartsWith('items/', $this->item->photo);
        $this->assertStringEndsWith('.webp', $this->item->photo);

        // Verificar que el archivo existe en S3 y es WebP
        Storage::disk('s3')->assertExists($this->item->photo);
        $content = Storage::disk('s3')->get($this->item->photo);
        $this->assertStringStartsWith('RIFF', $content);
        $this->assertStringContainsString('WEBP', substr($content, 0, 16));

        // Verificar que el archivo temporal se eliminó de local
        Storage::disk('local')->assertMissing($tempPath);
    }

    public function test_upload_item_photo_action_replaces_old_photo_on_s3(): void
    {
        $action = app(UploadItemPhotoAction::class);

        // 1. Subir primera foto
        $file1 = UploadedFile::fake()->image('foto1.jpg', 200, 200);
        $action->execute($this->item, $file1);

        $this->item->refresh();
        $firstPhoto = $this->item->photo;
        Storage::disk('s3')->assertExists($firstPhoto);

        // 2. Subir segunda foto (debe reemplazar y borrar la anterior)
        $file2 = UploadedFile::fake()->image('foto2.png', 200, 200);
        $action->execute($this->item, $file2);

        $this->item->refresh();
        $secondPhoto = $this->item->photo;

        $this->assertNotEquals($firstPhoto, $secondPhoto);
        Storage::disk('s3')->assertMissing($firstPhoto);
        Storage::disk('s3')->assertExists($secondPhoto);
    }

    public function test_api_authenticated_user_can_queue_photo_upload(): void
    {
        Queue::fake();

        $photo = UploadedFile::fake()->image('api_upload.jpg', 500, 500);

        $token = $this->user->createToken('test_token', ['*'], now()->addDay())->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson("/api/items/{$this->item->id}/photo/queue", [
                'photo' => $photo,
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
                'data' => [
                    'item_id' => $this->item->id,
                    'status' => 'queued',
                ],
            ]);

        Queue::assertPushed(UploadItemPhotoJob::class);
    }
}
