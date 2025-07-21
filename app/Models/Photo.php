<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_report_id', 
        'photo_path', 
        'descripcion',
        'taken_at'
    ];

    protected $casts = [
        'taken_at' => 'datetime',
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
