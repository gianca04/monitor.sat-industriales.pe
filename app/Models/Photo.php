<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id',
        'photo_path',
        'descripcion',
        'before_work_photo_path' // Nueva columna para la foto antes del trabajo
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($photo) {
            if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
                $fullPath = Storage::disk('public')->path($photo->photo_path);
                $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if ($ext === 'jpg' || $ext === 'jpeg') {
                    if (function_exists('exif_read_data')) {
                        $exif = @exif_read_data($fullPath);
                        if ($exif && isset($exif['Orientation'])) {
                            $orientation = $exif['Orientation'];
                            if ($orientation != 1) {
                                $img = imagecreatefromjpeg($fullPath);
                                $deg = 0;
                                switch ($orientation) {
                                    case 3:
                                        $deg = 180;
                                        break;
                                    case 6:
                                        $deg = 270;
                                        break;
                                    case 8:
                                        $deg = 90;
                                        break;
                                }
                                if ($deg) {
                                    $img = imagerotate($img, $deg, 0);
                                }
                                imagejpeg($img, $fullPath, 95);
                                imagedestroy($img);
                            }
                        }
                    }
                }
                // Puedes mantener el procesamiento para PNG y WEBP con Intervention Image si lo necesitas
            }
        });
    }

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function workReport()
    {
        return $this->belongsTo(WorkReport::class, 'work_report_id');
    }

    // Accessor para obtener la URL completa de la imagen
    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    // Accessor para verificar si la imagen existe
    public function getPhotoExistsAttribute()
    {
        return $this->photo_path ? Storage::exists($this->photo_path) : false;
    }
}
