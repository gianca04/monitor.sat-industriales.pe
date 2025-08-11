<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkReport extends Model
{
    use HasFactory;
    protected $table = 'work_reports';

    protected $fillable = [
        'employee_id',
        'project_id',
        'name',
        'description',
        'supervisor_signature',
        'manager_signature',
        'suggestions',
        'tools',
        'personnel',
        'materials',
        'start_time',  // Hora de inicio del trabajo
        'end_time',    // Hora de finalización del trabajo
        'report_date'  // Fecha del reporte (solo fecha)
    ];

    /**
     * Relación: Un reporte de trabajo pertenece a un empleado.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relación: Un reporte de trabajo pertenece a un proyecto.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    public function photos()
    {
        return $this->hasMany(Photo::class, 'work_report_id');
    }
}
