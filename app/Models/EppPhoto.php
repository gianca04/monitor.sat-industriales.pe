<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EppPhoto extends Model
{
    protected $fillable = ['epp_id', 'photo_path'];

    public function epp()
    {
        return $this->belongsTo(Epp::class);
    }
}
