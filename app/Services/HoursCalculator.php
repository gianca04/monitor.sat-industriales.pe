<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Timesheet;
use Carbon\Carbon;

class HoursCalculator
{
    /**
     * Calcula las horas trabajadas por un empleado
     * 
     * @param Attendance $attendance
     * @return array ['total_minutes' => int, 'formatted' => string]
     */
    public static function calculateWorkedHours(Attendance $attendance): array
    {
        // Verificar que existan los datos básicos
        if (!$attendance->check_in_date || !$attendance->check_out_date) {
            return [
                'total_minutes' => 0,
                'formatted' => null
            ];
        }

        $checkIn = Carbon::parse($attendance->check_in_date);
        $checkOut = Carbon::parse($attendance->check_out_date);

        // Calcular tiempo total en minutos
        $totalMinutes = $checkIn->diffInMinutes($checkOut);

        // Calcular tiempo de break en minutos del empleado
        $breakTime = static::calculateBreakTime($attendance);

        // Tiempo trabajado = tiempo total - tiempo de break
        $workedMinutes = max(0, $totalMinutes - $breakTime);

        return [
            'total_minutes' => $workedMinutes,
            'formatted' => static::formatMinutesToHours($workedMinutes)
        ];
    }

    /**
     * Calcula las horas extra trabajadas por un empleado
     * comparando con el horario establecido en el timesheet
     * 
     * @param Attendance $attendance
     * @param int|null $totalWorkedMinutes
     * @return array ['extra_minutes' => int, 'formatted' => string]
     */
    public static function calculateExtraHours(Attendance $attendance, ?int $totalWorkedMinutes = null): array
    {
        // Si no se proporciona el tiempo trabajado, calcularlo
        if ($totalWorkedMinutes === null) {
            $workedHours = static::calculateWorkedHours($attendance);
            $totalWorkedMinutes = $workedHours['total_minutes'];
        }

        // Validaciones básicas
        if (!$attendance->timesheet || $totalWorkedMinutes <= 0) {
            return [
                'extra_minutes' => 0,
                'formatted' => '0h 0m'
            ];
        }

        $standardWorkMinutes = static::calculateStandardWorkMinutes($attendance->timesheet);

        // Si no hay horario estándar definido, no hay horas extra
        if ($standardWorkMinutes <= 0) {
            return [
                'extra_minutes' => 0,
                'formatted' => '0h 0m'
            ];
        }

        // Calcular horas extra: diferencia entre tiempo trabajado y tiempo estándar
        $extraMinutes = max(0, $totalWorkedMinutes - $standardWorkMinutes);

        return [
            'extra_minutes' => $extraMinutes,
            'formatted' => static::formatMinutesToHours($extraMinutes)
        ];
    }

    /**
     * Calcula los minutos de trabajo estándar según el timesheet
     * 
     * @param Timesheet $timesheet
     * @return int
     */
    public static function calculateStandardWorkMinutes(Timesheet $timesheet): int
    {
        if (!$timesheet->check_in_date || !$timesheet->check_out_date) {
            return 0;
        }

        $timesheetCheckIn = Carbon::parse($timesheet->check_in_date);
        $timesheetCheckOut = Carbon::parse($timesheet->check_out_date);

        // Calcular tiempo total del horario estándar
        $standardTotalMinutes = $timesheetCheckIn->diffInMinutes($timesheetCheckOut);

        // Calcular tiempo de break del timesheet estándar
        $timesheetBreakTime = static::calculateTimesheetBreakTime($timesheet);

        // Minutos de trabajo estándar (total - break)
        return max(0, $standardTotalMinutes - $timesheetBreakTime);
    }

    /**
     * Calcula el tiempo de break del timesheet estándar
     * 
     * @param Timesheet $timesheet
     * @return int
     */
    public static function calculateTimesheetBreakTime(Timesheet $timesheet): int
    {
        // Si el timesheet tiene break configurado
        if ($timesheet->break_date && $timesheet->end_break_date) {
            $timesheetBreakStart = Carbon::parse($timesheet->break_date);
            $timesheetBreakEnd = Carbon::parse($timesheet->end_break_date);
            return $timesheetBreakStart->diffInMinutes($timesheetBreakEnd);
        }

        // Para turnos nocturnos, no hay break
        if ($timesheet->shift === 'night') {
            return 0;
        }

        // Para turnos diurnos sin break configurado, asumimos un break estándar de 1 hora
        return 60;
    }

    /**
     * Calcula el tiempo de break de un empleado específico
     * 
     * @param Attendance $attendance
     * @return int
     */
    public static function calculateBreakTime(Attendance $attendance): int
    {
        // Para turnos nocturnos, no hay break
        if ($attendance->shift === 'night') {
            return 0;
        }

        // Si el empleado tiene break registrado
        if ($attendance->break_date && $attendance->end_break_date) {
            $breakStart = Carbon::parse($attendance->break_date);
            $breakEnd = Carbon::parse($attendance->end_break_date);
            return $breakStart->diffInMinutes($breakEnd);
        }

        return 0;
    }

    /**
     * Convierte minutos a formato de horas legible
     * 
     * @param int $minutes
     * @return string|null
     */
    public static function formatMinutesToHours(int $minutes): ?string
    {
        if ($minutes <= 0) {
            return null;
        }

        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m";
    }

    /**
     * Obtiene un resumen completo de horas para un empleado
     * 
     * @param Attendance $attendance
     * @return array
     */
    public static function getHoursSummary(Attendance $attendance): array
    {
        $workedHours = static::calculateWorkedHours($attendance);
        $extraHours = static::calculateExtraHours($attendance, $workedHours['total_minutes']);
        $breakTime = static::calculateBreakTime($attendance);

        return [
            'worked_hours' => $workedHours,
            'extra_hours' => $extraHours,
            'break_time' => [
                'total_minutes' => $breakTime,
                'formatted' => static::formatMinutesToHours($breakTime)
            ],
            'standard_hours' => [
                'total_minutes' => $attendance->timesheet ? static::calculateStandardWorkMinutes($attendance->timesheet) : 0,
                'formatted' => $attendance->timesheet ? static::formatMinutesToHours(static::calculateStandardWorkMinutes($attendance->timesheet)) : null
            ]
        ];
    }

    /**
     * Verifica si un empleado trabajó horas extra
     * 
     * @param Attendance $attendance
     * @return bool
     */
    public static function hasExtraHours(Attendance $attendance): bool
    {
        $extraHours = static::calculateExtraHours($attendance);
        return $extraHours['extra_minutes'] > 0;
    }

    /**
     * Calcula el porcentaje de asistencia de un empleado para un período
     * 
     * @param int $attendedDays
     * @param int $totalWorkDays
     * @return float
     */
    public static function calculateAttendancePercentage(int $attendedDays, int $totalWorkDays): float
    {
        if ($totalWorkDays <= 0) {
            return 0.0;
        }

        return round(($attendedDays / $totalWorkDays) * 100, 2);
    }
}