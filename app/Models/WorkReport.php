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
