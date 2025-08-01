<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Tabla asociada
    protected $table = 'employees';

    // Atributos asignables en masa
    protected $fillable = [
        'document_type', // DNI','PASAPORTE', 'CARNET DE EXTRANJERIA
        'document_number',
        'first_name',
        'last_name',
        'address',
        'date_contract',
        'date_birth',
        'sex', // male', 'female', 'other
        'position_id',
        'active',
    ];

    // Casts para fechas
    protected $casts = [
        'date_contract' => 'date',
        'date_birth' => 'date',
        'position_id' => 'integer',
        'active' => 'boolean',
    ];
    public function position()
    {
        return $this->belongsTo(Position::class);
    }
    public function getTitleAttribute()
    {
        return $this->full_name;
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class, 'employee_id'); // Relación con la tabla quotes
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name . ' - ' . $this->document_number;
    }

    // Relación muchos a muchos usando la tabla pivote y el modelo EmployeeProject
    public function employeeProjects()
    {
        return $this->hasMany(EmployeeProject::class, 'employee_id');
    }

    // Relación directa a proyectos a través de la tabla pivote
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'employee_project')
            ->withTimestamps();
    }
    // Scope para empleados activos
    public function scopeActive($query)
    {
        return $query; // Por ahora retorna todos, puedes agregar condiciones si tienes un campo 'active'
    }
}
