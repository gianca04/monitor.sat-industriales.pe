<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class Epp extends Model
{
    protected $fillable = ['name', 'description', 'brand_id', 'model', 'photos', 'active'];

    protected $casts = [
        'photos' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Epp $epp) {
            if ($epp->photos && is_array($epp->photos)) {
                $convertedPhotos = [];
                $manager = new ImageManager(new Driver());

                foreach ($epp->photos as $photoPath) {
                    if (Storage::disk('public')->exists($photoPath)) {
                        $fullPath = Storage::disk('public')->path($photoPath);
                        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

                        if ($extension === 'webp') {
                            $convertedPhotos[] = $photoPath;
                            continue;
                        }

                        try {
                            $image = $manager->read($fullPath);

                            // Auto-orient based on EXIF if jpg/jpeg
                            if (in_array($extension, ['jpg', 'jpeg']) && function_exists('exif_read_data')) {
                                $exif = @exif_read_data($fullPath);
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

                            // Generate new webp filename
                            $newFilename = pathinfo($photoPath, PATHINFO_DIRNAME) . '/' . pathinfo($photoPath, PATHINFO_FILENAME) . '.webp';
                            $newFullPath = Storage::disk('public')->path($newFilename);

                            // Save as WebP
                            $image->toWebp(85)->save($newFullPath);

                            // Delete original file
                            Storage::disk('public')->delete($photoPath);

                            $convertedPhotos[] = $newFilename;
                        } catch (\Exception $e) {
                            Log::error("Failed to convert EPP image to WebP: " . $e->getMessage());
                            $convertedPhotos[] = $photoPath; // Fallback to original
                        }
                    } else {
                        $convertedPhotos[] = $photoPath;
                    }
                }

                $epp->photos = $convertedPhotos;
            }
        });
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'epp_subcategory');
    }

    public function certifications()
    {
        return $this->belongsToMany(Certification::class, 'certification_epp');
    }

    public function variants()
    {
        return $this->hasMany(EppVariant::class);
    }

    public function stocks()
    {
        return $this->hasManyThrough(Stock::class, EppVariant::class);
    }

    public function stockMovements()
    {
        return $this->hasManyThrough(StockMovement::class, EppVariant::class);
    }

    public function getCurrentStockAttribute(): int
    {
        return (int) $this->stocks()->sum('current_stock');
    }

    public function getIsBelowMinimumAttribute(): bool
    {
        return $this->variants->contains(fn ($variant) => $variant->is_below_minimum);
    }

    public function getRequiresReplenishmentAttribute(): bool
    {
        return $this->is_below_minimum;
    }
}

