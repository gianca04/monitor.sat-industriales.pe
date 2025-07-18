<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Timesheet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OverviewStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Clientes', Client::count())
                ->description('Total de clientes')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Empleados', Employee::count())
                ->description('Total de empleados')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Proyectos', Project::count())
                ->description('Total de proyectos')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Cotizaciones', Quote::count())
                ->description('Total de cotizaciones')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Tareos', Timesheet::count())
                ->description('Total de tareos')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }

    protected function getColumns(): int
    {
        return 5;
    }
}
