<?php

namespace App\Observers;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;

class PhotoObserver
{
    /**
     * Handle the Photo "deleted" event.
     *
     * @param  \App\Models\Photo  $photo
     * @return void
     */
    public function deleted(Photo $photo)
    {
        // Eliminar la imagen inicial si existe
        if ($photo->before_work_photo_path && Storage::disk('public')->exists($photo->before_work_photo_path)) {
            Storage::disk('public')->delete($photo->before_work_photo_path);
        }

        // Eliminar la imagen del trabajo realizado si existe
        if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
            Storage::disk('public')->delete($photo->photo_path);
        }
    }
}
