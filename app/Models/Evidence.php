<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evidence extends Model
{
    use HasFactory;
    protected $table = 'work_reports';

    protected $fillable = [
        'name',
        'description',
    ];

    public function photos() {
        return $this->hasMany(Photo::class);
    }
}
