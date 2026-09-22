<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'sub_client_id',
        'activity_name',
        'created_by',
    ];

    public function subClient(): BelongsTo
    {
        return $this->belongsTo(SubClient::class);
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
