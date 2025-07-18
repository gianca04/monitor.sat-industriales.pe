<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubClient extends Model
{
    use HasFactory;

    protected $table = 'sub_clients';

    protected $fillable = [
        'client_id',
        'name',
        'description',
        'location',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'location' => 'string',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function quotes()
    {
        return $this->hasMany(Quote::class, 'employee_id'); // Relación con la tabla quotes
    }
}
