<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'subcategory_id',
        'unit_id',
        'photo',
        'created_by',
    ];

    /**
     * Get the resolved URL for the item's photo from S3.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return app(\App\Services\ItemPhotoService::class)->url($this->photo);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function requirementLists(): HasMany
    {
        return $this->hasMany(RequirementList::class);
    }
}
