<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeeStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalEmployees = Employee::count();
        $recentEmployees = Employee::whereDate('created_at', '>=', Carbon::now()->subDays(30))->count();
        $thisMonthEmployees = Employee::whereMonth('created_at', Carbon::now()->month)->count();

        return [
            Stat::make('Total Empleados', $totalEmployees)
                ->description('Empleados registrados')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Recientes', $recentEmployees)
                ->description('Últimos 30 días')
                ->descriptionIcon('heroicon-m-clock')
                ->color('success'),

            Stat::make('Nuevos este Mes', $thisMonthEmployees)
                ->description('Registrados en ' . Carbon::now()->format('M'))
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
        ];
    }
}
