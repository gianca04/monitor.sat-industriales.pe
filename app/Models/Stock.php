<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $fillable = ['warehouse_id', 'warehouse_location_id', 'epp_variant_id', 'current_stock', 'minimum_stock', 'maximum_stock'];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLocation()
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function eppVariant()
    {
        return $this->belongsTo(EppVariant::class);
    }
}
