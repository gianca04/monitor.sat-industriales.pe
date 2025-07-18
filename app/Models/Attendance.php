<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'timesheet_id',
        'employee_id',
        'status',
        'shift',
        'check_in_date',
        'break_date',
        'end_break_date',
        'check_out_date',
        'observation',
    ];

    protected $casts = [
        'check_in_date'   => 'datetime',
        'break_date'      => 'datetime',
        'end_break_date'  => 'datetime',
        'check_out_date'  => 'datetime',
        'shift'           => 'string',
        'status'          => 'string',
    ];

    // Relaciones
    public function timesheet()
    {
        return $this->belongsTo(Timesheet::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
