<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'warehouse_id',
        'warehouse_location_id',
        'epp_variant_id',
        'delivery_detail_id',
        'quantity',
        'type',
        'description',
    ];

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

    public function deliveryDetail()
    {
        return $this->belongsTo(DeliveryDetail::class);
    }
}
