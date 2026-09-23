<?php

namespace App\Actions;

use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Http\UploadedFile;

class UploadItemPhotoAction
{
    public function __construct(
        protected ItemPhotoService $photoService
    ) {}

    /**
     * Sube o reemplaza la fotografía de un ítem a S3 convirtiéndola a WebP
     * y actualiza el campo 'photo' en el modelo.
     *
     * @param  UploadedFile|string  $file  Archivo subido o ruta física al archivo
     * @param  int  $quality  Calidad de compresión WebP (1-100)
     * @return Item Modelo Item con el campo photo actualizado
     */
    public function execute(Item $item, UploadedFile|string $file, int $quality = 85): Item
    {
        $oldPhoto = $item->photo;
        $storedPath = $this->photoService->upload($file, $oldPhoto, $quality);

        $item->update(['photo' => $storedPath]);

        return $item;
    }
}
