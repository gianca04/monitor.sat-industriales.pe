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
        'address',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'location' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'address' => 'string',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public function quotes()
    {
        return $this->hasMany(Quote::class, 'employee_id'); // Relación con la tabla quotes
    }

    /**
     * Get the latitude from the location JSON field
     */
    public function getLocationLatitudeAttribute()
    {
        if (!$this->location || !is_array($this->location)) return null;
        return $this->location['latitude'] ?? null;
    }

    /**
     * Get the longitude from the location JSON field
     */
    public function getLocationLongitudeAttribute()
    {
        if (!$this->location || !is_array($this->location)) return null;
        return $this->location['longitude'] ?? null;
    }

    public function getLocationAddressAttribute()
    {
        if (!$this->location || !is_array($this->location)) return null;
        return $this->location['location'] ?? null;
    }

    /**
     * Get formatted coordinates as string
     */
    public function getCoordinatesAttribute()
    {
        $lat = $this->location_latitude;
        $lng = $this->location_longitude;

        if ($lat && $lng) {
            return sprintf('%.6f, %.6f', $lat, $lng);
        }

        return null;
    }

    public function contactData()
    {
        return $this->hasMany(ContactData::class);
    }
}
