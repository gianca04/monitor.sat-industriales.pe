<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EppVariant extends Model
{
    protected $fillable = ['epp_id', 'sku', 'variant_name', 'active'];

    public function generateSku(): string
    {
        $epp = $this->epp ?? Epp::find($this->epp_id);
        
        $brandSlug = ($epp && $epp->brand) ? \Illuminate\Support\Str::slug($epp->brand->name) : '';
        $eppSlug = $epp ? \Illuminate\Support\Str::slug($epp->name) : '';
        $variantSlug = $this->variant_name ? \Illuminate\Support\Str::slug($this->variant_name) : '';
        
        // Take first 3 letters of EPP name if it's too long, or just use the slugs
        $parts = array_filter([$brandSlug ?: null, $eppSlug ?: null, $variantSlug ?: null]);
        
        return strtoupper(implode('-', $parts)) ?: 'SKU-' . rand(1000, 9999);
    }

    public function epp()
    {
        return $this->belongsTo(Epp::class);
    }

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }
}
