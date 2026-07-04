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
        'user_id',
        'quantity',
        'type',
        'description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($movement) {
            if (auth()->check() && !$movement->user_id) {
                $movement->user_id = auth()->id();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
