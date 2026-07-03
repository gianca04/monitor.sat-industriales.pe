<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epp extends Model
{
    protected $fillable = ['name', 'description', 'brand_id', 'model', 'photos', 'active'];

    protected $casts = [
        'photos' => 'array',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'epp_subcategory');
    }

    public function certifications()
    {
        return $this->belongsToMany(Certification::class, 'certification_epp');
    }

    public function variants()
    {
        return $this->hasMany(EppVariant::class);
    }

    public function stocks()
    {
        return $this->hasManyThrough(Stock::class, EppVariant::class);
    }
}
