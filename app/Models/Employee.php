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
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'address',
        'date_contract',
        'date_birth',
        'sex',
    ];

    // Casts para fechas
    protected $casts = [
        'date_contract' => 'date',
        'date_birth' => 'date',
    ];

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

    // Scope para empleados activos
    public function scopeActive($query)
    {
        return $query; // Por ahora retorna todos, puedes agregar condiciones si tienes un campo 'active'
    }
}
