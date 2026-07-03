<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name', 'location'];

    public function locations()
    {
        return $this->hasMany(WarehouseLocation::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
