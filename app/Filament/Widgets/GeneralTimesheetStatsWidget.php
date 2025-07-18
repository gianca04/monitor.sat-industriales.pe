<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GeneralTimesheetStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // Estadísticas de hoy
        $today = Carbon::today();
        $todayTimesheets = Timesheet::whereDate('check_in_date', $today)->count();
        $todayAttendances = Attendance::whereHas('timesheet', function ($query) use ($today) {
            $query->whereDate('check_in_date', $today);
        });

        $totalTodayAttendances = $todayAttendances->count();
        $todayPresent = $todayAttendances->where('status', 'attended')->count();
        $todayAbsent = $todayAttendances->where('status', 'absent')->count();

        // Estadísticas del mes actual
        $thisMonth = Carbon::now()->startOfMonth();
        $monthlyTimesheets = Timesheet::where('check_in_date', '>=', $thisMonth)->count();

        // Calcular porcentaje de asistencia del día
        $attendancePercentage = $totalTodayAttendances > 0
            ? round(($todayPresent / $totalTodayAttendances) * 100, 1)
            : 0;

        return [
            Stat::make('Tareos de Hoy', $todayTimesheets)
                ->description('Tareos programados para hoy')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary')
                ->chart([7, 12, 8, 15, 18, 22, $todayTimesheets]),

            Stat::make('Asistencias Hoy', $totalTodayAttendances)
                ->description("Asistencia: {$attendancePercentage}%")
                ->descriptionIcon('heroicon-m-users')
                ->color($attendancePercentage >= 85 ? 'success' : ($attendancePercentage >= 70 ? 'warning' : 'danger'))
                ->chart([$todayAbsent, $todayPresent]),

            Stat::make('Presentes', $todayPresent)
                ->description('Empleados que asistieron hoy')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Tareos del Mes', $monthlyTimesheets)
                ->description('Total tareos ' . Carbon::now()->format('F Y'))
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
