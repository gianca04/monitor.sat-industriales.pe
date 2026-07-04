<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = [
        'employee_id',
        'sub_client_id',
        'warehouse_id',
        'delivered_by',
        'delivery_date',
        'deadline_date',
        'observations',
        'status',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'deadline_date' => 'datetime',
        'status' => DeliveryStatus::class,
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function subClient()
    {
        return $this->belongsTo(SubClient::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function deliverer()
    {
        return $this->belongsTo(Employee::class, 'delivered_by');
    }

    public function details()
    {
        return $this->hasMany(DeliveryDetail::class);
    }
}
