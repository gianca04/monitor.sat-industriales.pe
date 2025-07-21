<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Project
 *
 * Represents a project entity with location and scheduling data.
 */
class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'location',
        'latitude',
        'longitude',
        'quote_id'
    ];



    /**
     * Relación con la cotización
     * Un proyecto pertenece a una cotización.
     */
    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
    /**
     * The attributes that should be cast to native types.
     *
     * This ensures proper handling of date and decimal types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'location' => 'array', // Convierte automáticamente entre JSON y array

    ];

    public function clients()
    {
        return $this->belongsToMany(Client::class, 'client_project');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
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

    public function Work_reports()
    {
        return $this->belongsToMany(WorkReport::class, 'work_report_project')
            ->withTimestamps();
    }

    /**
     * Get the address from the location JSON field
     */
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

    /**
     * Check if project is currently active (within date range)
     */
    public function getIsActiveAttribute()
    {
        $now = now()->toDateString();
        return $this->start_date <= $now && $this->end_date >= $now;
    }
}
