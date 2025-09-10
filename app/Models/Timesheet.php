<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'project_id',
        'shift',
        'check_in_date',
        'break_date',
        'end_break_date',
        'check_out_date',
    ];

    /**
     * Boot function to add model events
     */
    protected static function boot()
    {
        parent::boot();

        // Validar antes de crear un nuevo timesheet
        static::creating(function ($timesheet) {
            $existingTimesheet = static::where('project_id', $timesheet->project_id)
                ->whereDate('check_in_date', \Carbon\Carbon::parse($timesheet->check_in_date)->toDateString())
                ->first();

            if ($existingTimesheet) {
                throw new \Exception('Ya existe un tareo para este proyecto en la fecha seleccionada.');
            }
        });

        // Validar antes de actualizar un timesheet
        static::updating(function ($timesheet) {
            $existingTimesheet = static::where('project_id', $timesheet->project_id)
                ->whereDate('check_in_date', \Carbon\Carbon::parse($timesheet->check_in_date)->toDateString())
                ->where('id', '!=', $timesheet->id)
                ->first();

            if ($existingTimesheet) {
                throw new \Exception('Ya existe un tareo para este proyecto en la fecha seleccionada.');
            }
        });
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

        'check_in_date'   => 'datetime',
        'break_date'      => 'datetime',
        'end_break_date' => 'datetime',
        'check_out_date'  => 'datetime',
        'shift'           => 'string',
    ];

    /**
     * Get the employee that owns the timesheet.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the project that owns the timesheet.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
        public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
