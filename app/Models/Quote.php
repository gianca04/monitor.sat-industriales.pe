<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth; // ¡Esta es la que necesitas!
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory;

    // Definir el nombre de la tabla (si no sigue la convención plural)
    protected $table = 'quotes';

    // Los atributos que pueden ser asignados masivamente
    protected $fillable = [
        'client_id',
        'employee_id',
        'sub_client_id',
        'TDR',
        'quote_file',        // Nuevo campo para el archivo de cotización
        'correlative',
        'contractor',
        'pe_pt',
        'project_description',
        'location',

        'delivery_term',
        'status',
        'comment',
    ];

    // Definir los casts de atributos
    protected $casts = [
        'delivery_term' => 'date',  // 'plazo_entrega' es un tipo de dato fecha
        'pe_pt' => 'string',  // 'pe_pt' es un enum, lo tratamos como string
    ];

    public function evidences(){
        return $this->belongsToMany(Evidence::class, 'visits');
    }

    /**
     * Relación con el modelo Employee
     * Una cotización pertenece a un solo empleado.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Relación con el modelo Client
     * Una cotización pertenece a un solo cliente (sub cliente en este caso).
     */
    public function sub_client()
    {
        return $this->belongsTo(SubClient::class, 'sub_client_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }


    protected static function booted()
    {
        static::creating(function ($quote) {
            $subClient = $quote->sub_client;
            $siglas = '';
            if ($subClient) {
                $siglas = strtoupper(substr($subClient->name, 0, strpos($subClient->name . ' ', ' ')));
                $siglas = substr($siglas, 0, 3);
            }
            $month = now()->format('m');
            $year = now()->format('y');
            $correlative = 'SAT';
            if ($siglas) {
                $correlative .= '-' . $siglas;
            }
            if ($quote->pe_pt) {
                $correlative .= '-' . $quote->pe_pt;
            }
            $correlative .= '-' . $month . $year . '-' . ($quote->id ?? 'NEW');

            $quote->correlative = $correlative;
        });

        static::created(function ($quote) {
            $subClient = $quote->sub_client;
            $siglas = '';
            if ($subClient) {
                $siglas = strtoupper(substr($subClient->name, 0, strpos($subClient->name . ' ', ' ')));
                $siglas = substr($siglas, 0, 3);
            }
            $month = $quote->created_at->format('m');
            $year = $quote->created_at->format('y');
            $correlative = 'SAT';
            if ($siglas) {
                $correlative .= '-' . $siglas;
            }
            if ($quote->pe_pt) {
                $correlative .= '-' . $quote->pe_pt;
            }
            $correlative .= '-' . $month . $year . '-' . $quote->id;

            if ($quote->correlative !== $correlative) {
                $quote->correlative = $correlative;
                $quote->saveQuietly();
            }
        });
    }

    /**
     * Relación con los proyectos
     * Una cotización puede generar varios proyectos.
     */
    public function projects()
    {
        return $this->hasMany(Project::class, 'quote_id');
    }
}
