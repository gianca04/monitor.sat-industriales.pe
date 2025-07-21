<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\WorkDay;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = ['evidence_id', 'photo_path', 'descripcion'];

    public function evidence() {
        return $this->belongsTo(WorkDay::class);
    }
}
