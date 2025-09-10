<?php

namespace App\Filament\Resources\TimesheetResource\Widgets;

use App\Models\Attendance;
use App\Models\Timesheet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class TimesheetStatsWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        if (!$this->record) {
            return [];
        }

        $timesheet = $this->record;
        $attendances = $timesheet->attendances();

        $totalAttendances = $attendances->count();
        $presentCount = $attendances->where('status', 'attended')->count();
        $absentCount = $attendances->where('status', 'absent')->count();
        $justifiedCount = $attendances->where('status', 'justified')->count();

        $presentPercentage = $totalAttendances > 0 ? round(($presentCount / $totalAttendances) * 100, 1) : 0;

        return [
            Stat::make('Total Registros', $totalAttendances)
                ->description('Asistencias registradas')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Asistieron', $presentCount)
                ->description("{$presentPercentage}% del total")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Faltaron', $absentCount)
                ->description('Ausencias sin justificar')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Justificados', $justifiedCount)
                ->description('Ausencias justificadas')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
