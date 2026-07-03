<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name'];

    public function epps()
    {
        return $this->hasMany(Epp::class);
    }
}
