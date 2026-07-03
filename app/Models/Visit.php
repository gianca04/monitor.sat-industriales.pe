<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    //
    protected $fillable = [
        'employee_id',
        'name',
        'description',
        'employee_signature',
        'manager_signature',
        'suggestions',
        'tools',
        'materials',
        'start_time',  // Hora de inicio del trabajo
        'end_time',    // Hora de finalizaci��n del trabajo
        'report_date'  // Fecha del reporte (solo fecha)
    ];


    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function visitPhotos()
    {
        return $this->hasMany(VisitPhoto::class, 'visit_id');
    }
    public function requests()
    {
        return $this->belongsToMany(Request::class, 'request_visit', 'visit_id', 'request_id');
    }
}
