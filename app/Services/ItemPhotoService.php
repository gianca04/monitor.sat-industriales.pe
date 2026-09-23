<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ItemPhotoService
{
    protected ImageManager $imageManager;

    protected string $disk = 's3';

    public function __construct(?ImageManager $imageManager = null)
    {
        $this->imageManager = $imageManager ?? new ImageManager(new Driver);
    }

    /**
     * Upload an item photo to S3, converting it to WebP format.
     * Optionally deletes the old photo from S3 if provided.
     *
     * @return string Relative path in S3 (e.g. 'items/abc.webp')
     */
    public function upload(UploadedFile|string $file, ?string $oldPhotoPath = null, int $quality = 85): string
    {
        $realPath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        // Leer la imagen usando Intervention Image v3
        $image = $this->imageManager->read($realPath);

        // Auto-orientar con EXIF si es JPG/JPEG
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($realPath);
            if ($exif && isset($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $image->rotate(180);
                        break;
                    case 6:
                        $image->rotate(270);
                        break;
                    case 8:
                        $image->rotate(90);
                        break;
                }
            }
        }

        // Convertir a formato WebP
        $encoded = $image->toWebp($quality);

        // Generar nombre de archivo único
        $filename = 'items/'.Str::uuid().'.webp';

        // Guardar en el disco S3
        Storage::disk($this->disk)->put($filename, (string) $encoded, 'public');

        // Si existe una foto anterior, eliminarla de S3
        if ($oldPhotoPath) {
            $this->delete($oldPhotoPath);
        }

        return $filename;
    }

    /**
     * Delete an item photo from S3.
     */
    public function delete(?string $photoPath): bool
    {
        if (empty($photoPath)) {
            return false;
        }

        try {
            if (Storage::disk($this->disk)->exists($photoPath)) {
                return Storage::disk($this->disk)->delete($photoPath);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo eliminar la foto de S3 [{$photoPath}]: ".$e->getMessage());
        }

        return false;
    }

    /**
     * Get the public URL for an item photo from S3.
     */
    public function url(?string $photoPath): ?string
    {
        if (empty($photoPath)) {
            return null;
        }

        if (str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')) {
            return $photoPath;
        }

        return Storage::disk($this->disk)->url($photoPath);
    }
}
