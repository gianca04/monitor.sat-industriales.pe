<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TimesheetStatsWidget extends BaseWidget
{
    /*protected function getStats(): array
    {
        $totalTimesheets = Timesheet::count();
        $todayTimesheets = Timesheet::whereDate('check_in_date', Carbon::today())->count();
        $thisWeekTimesheets = Timesheet::whereBetween('check_in_date', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();

        return [
            
            Stat::make('Total Tareos', $totalTimesheets)
                ->description('Tareos registrados')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),

            Stat::make('Tareos de Hoy', $todayTimesheets)
                ->description('Registrados hoy')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Esta Semana', $thisWeekTimesheets)
                ->description('Semana actual')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
                
        ];
    }
    */
}
