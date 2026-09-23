<?php

namespace Tests\Unit;

use App\Services\ItemPhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ItemPhotoServiceTest extends TestCase
{
    protected ItemPhotoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->service = new ItemPhotoService;
    }

    public function test_uploads_image_and_converts_to_webp_on_s3(): void
    {
        // Arrange: Crear imagen simulada en PNG
        $file = UploadedFile::fake()->image('herramienta.png', 400, 300);

        // Act: Subir a través del servicio
        $storedPath = $this->service->upload($file);

        // Assert
        $this->assertNotEmpty($storedPath);
        $this->assertStringStartsWith('items/', $storedPath);
        $this->assertStringEndsWith('.webp', $storedPath);

        // Verificar que el archivo existe en el disco S3
        Storage::disk('s3')->assertExists($storedPath);

        // Verificar que los primeros bytes corresponden a un archivo WebP (RIFF....WEBP)
        $content = Storage::disk('s3')->get($storedPath);
        $this->assertStringStartsWith('RIFF', $content);
        $this->assertStringContainsString('WEBP', substr($content, 0, 16));
    }

    public function test_upload_replaces_old_photo_on_s3(): void
    {
        // Arrange: Simular foto previa en S3
        $oldPath = 'items/antigua-foto.webp';
        Storage::disk('s3')->put($oldPath, 'contenido anterior');
        Storage::disk('s3')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->image('nueva-herramienta.jpg', 200, 200);

        // Act: Subir nueva foto indicando la ruta anterior
        $newPath = $this->service->upload($newFile, $oldPath);

        // Assert: La foto anterior debe eliminarse y la nueva debe existir
        Storage::disk('s3')->assertMissing($oldPath);
        Storage::disk('s3')->assertExists($newPath);
        $this->assertNotEquals($oldPath, $newPath);
    }

    public function test_delete_removes_photo_from_s3(): void
    {
        // Arrange
        $path = 'items/a-eliminar.webp';
        Storage::disk('s3')->put($path, 'archivo para borrar');
        Storage::disk('s3')->assertExists($path);

        // Act
        $result = $this->service->delete($path);

        // Assert
        $this->assertTrue($result);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_delete_handles_non_existent_or_empty_path(): void
    {
        $this->assertFalse($this->service->delete(null));
        $this->assertFalse($this->service->delete(''));
        $this->assertFalse($this->service->delete('items/inexistente.webp'));
    }

    public function test_url_resolves_s3_url_correctly(): void
    {
        $path = 'items/taladro-bosch.webp';
        $url = $this->service->url($path);

        $this->assertNotNull($url);
        $this->assertStringContainsString($path, $url);

        // Ruta nula debe retornar null
        $this->assertNull($this->service->url(null));

        // URL absoluta existente no debe ser alterada
        $absoluteUrl = 'https://ejemplo.com/foto.jpg';
        $this->assertEquals($absoluteUrl, $this->service->url($absoluteUrl));
    }
}
