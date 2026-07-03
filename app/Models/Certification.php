<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $fillable = ['code', 'name', 'description'];

    public function epps()
    {
        return $this->belongsToMany(Epp::class, 'certification_epp');
    }
}
