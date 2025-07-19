<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['evidence_id', 'name', 'photo_path', 'descripcion'];

    public function evidence() {
        return $this->belongsTo(Evidence::class);
    }
}
