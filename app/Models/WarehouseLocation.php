<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseLocation extends Model
{
    protected $fillable = ['warehouse_id', 'code', 'area', 'rack', 'shelf', 'section', 'bin', 'description'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code = $model->generateCode();
        });

        static::updating(function ($model) {
            if (empty($model->code)) {
                $model->code = $model->generateCode();
            }
        });
    }

    public function generateCode(): string
    {
        $parts = [];
        
        foreach (['area', 'rack', 'shelf', 'section', 'bin'] as $field) {
            if (!empty($this->$field)) {
                $parts[] = strtoupper(trim($this->$field));
            }
        }

        return implode('-', $parts) ?: 'LOC-TEMP';
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
