<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;


    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'reference',
        'request_number', // NÚMERO DE SOLICITUD
        'description',
        'sub_client_id',
        'cotizador_id',
        'supervisor_id',
        'visit_date',
        'check_in_time',
        'check_out_time',
        'submission_date',
        'budget',
        'status',
        'comments',
    ];

    protected $casts = [
        'sub_client_id' => 'integer',
        'cotizador_id' => 'integer',
        'supervisor_id' => 'integer',
        'visit_date' => 'date',
        'check_in_time' => 'datetime:H:i',
        'check_out_time' => 'datetime:H:i',
        'submission_date' => 'date',
        'budget' => 'decimal:2',
    ];


    /**
     * Devuelve un array de los 'reference' de los requests con status 'attended'.
     */
    public static function getAttendedReferences()
    {
        return self::where('status', 'attended')->pluck('reference');
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            // 👇 CORREGIDO
            $q->where('requests.reference', 'LIKE', "%{$term}%")
                // 👇 CORREGIDO
                ->orWhere('requests.description', 'LIKE', "%{$term}%")
                ->orWhere('sub_clients.name', 'LIKE', "%{$term}%");
        });
    }

    public static function generarCorrelativo()
    {
        $year = date('Y');
        $month = date('m');

        // Obtenemos el último número secuencial del mes actual

        $number = self::where('reference', 'like', "SAT-CQT{$month}{$year}-%")
            ->orderBy('reference', 'desc')
            ->first();

        if ($number) {
            $partes = explode('-', $number->reference);
            $lastNumber = (int) end($partes);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return "SAT-CQT{$month}{$year}-{$newNumber}";
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->reference)) {
                $request->reference = self::generarCorrelativo();
            }
        });
    }
    /**
     * Relationship with SubClient
     */
    public function subClient()
    {
        return $this->belongsTo(SubClient::class, 'sub_client_id');
    }

    /**
     * Relationship with Employee (Cotizador/Estimator)
     */
    public function cotizador()
    {
        return $this->belongsTo(Employee::class, 'cotizador_id');
    }

    /**
     * Relationship with Employee (Supervisor)
     */
    public function supervisor()
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    /**
     * Relationship with WorkOrder (one-to-many)
     */
    public function workOrders()
    {
        return $this->hasMany(WorkOrder::class, 'request_id');
    }

    /**
     * Relationship with Visit (many-to-many)
     */
    public function visitas()
    {
        return $this->belongsToMany(Visit::class, 'request_visit', 'request_id', 'visit_id');
    }

    /**
     * Accessor for formatted budget
     */
    public function getFormattedBudgetAttribute()
    {
        return $this->budget ? number_format((float) $this->budget, 2) : null;
    }

    /**
     * Accessor for full reference display
     */
    public function getFullReferenceAttribute()
    {
        return $this->request_number ?? $this->reference;
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for attended requests
     */
    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    /**
     * Scope for rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope for requests with visit date
     */
    public function scopeWithVisitDate($query)
    {
        return $query->whereNotNull('visit_date');
    }

    /**
     * Scope for requests within date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('visit_date', [$startDate, $endDate]);
    }

    public function visitPhotos()
    {
        return $this->hasManyThrough(VisitPhoto::class, Visit::class, 'id', 'visit_id', 'id', 'id')
            ->join('request_visit', 'visits.id', '=', 'request_visit.visit_id')
            ->where('request_visit.request_id', $this->id);
    }

    public function workOrder()
    {
        return $this->hasOne(WorkOrder::class, 'request_id');
    }
}
