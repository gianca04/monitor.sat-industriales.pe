<?php

namespace App\Observers;

use App\Models\Epp;
use Illuminate\Support\Facades\Storage;

class EppObserver
{
    /**
     * Handle the Epp "updating" event.
     */
    public function updating(Epp $epp): void
    {
        $originalPhotos = $epp->getOriginal('photos') ?: [];
        if (is_string($originalPhotos)) {
            $originalPhotos = json_decode($originalPhotos, true) ?: [];
        }
        
        $currentPhotos = $epp->photos ?: [];

        // Find photos that were removed from the array
        $removedPhotos = array_diff($originalPhotos, $currentPhotos);

        foreach ($removedPhotos as $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
        }
    }

    /**
     * Handle the Epp "deleted" event.
     */
    public function deleted(Epp $epp): void
    {
        $photos = $epp->photos ?: [];
        foreach ($photos as $photoPath) {
            if (Storage::disk('public')->exists($photoPath)) {
                Storage::disk('public')->delete($photoPath);
            }
        }
    }
}
