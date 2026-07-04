<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryDetail extends Model
{
    protected $fillable = [
        'delivery_id',
        'epp_variant_id',
        'quantity',
        'employee_id',
        'sub_client_id',
        'status',
    ];

    protected $casts = [
        'status' => \App\Enums\DeliveryStatus::class,
    ];

    public function delivery()
    {
        return $this->belongsTo(Delivery::class);
    }

    public function eppVariant()
    {
        return $this->belongsTo(EppVariant::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function subClient()
    {
        return $this->belongsTo(SubClient::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }

    public function getDeliveredQuantityAttribute(): int
    {
        return (int) $this->stockMovements()
            ->where('type', 'dispatch')
            ->sum('quantity');
    }
}

