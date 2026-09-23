<?php

namespace App\Jobs;

use App\Actions\UploadItemPhotoAction;
use App\Models\Item;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UploadItemPhotoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Item  $item  El ítem al que se asociará la fotografía
     * @param  string  $tempPath  Ruta relativa en el disco 'local' o ruta física del archivo temporal
     * @param  int  $quality  Calidad de compresión WebP
     */
    public function __construct(
        public Item $item,
        public string $tempPath,
        public int $quality = 85
    ) {}

    /**
     * Execute the job.
     */
    public function handle(UploadItemPhotoAction $action): void
    {
        $disk = Storage::disk('local');
        $fullPath = $disk->exists($this->tempPath)
            ? $disk->path($this->tempPath)
            : (file_exists($this->tempPath) ? $this->tempPath : null);

        if (! $fullPath || ! file_exists($fullPath)) {
            Log::warning("UploadItemPhotoJob: Archivo temporal no encontrado [{$this->tempPath}] para Item #{$this->item->id}");

            return;
        }

        try {
            $action->execute($this->item, $fullPath, $this->quality);
        } finally {
            if ($disk->exists($this->tempPath)) {
                $disk->delete($this->tempPath);
            } elseif (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}
