<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ClientStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalClients = Client::count();
        $activeClients = Client::whereHas('projects')->count();
        $thisMonthClients = Client::whereMonth('created_at', Carbon::now()->month)->count();

        return [
            Stat::make('Total Clientes', $totalClients)
                ->description('Clientes registrados')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('primary'),

            Stat::make('Clientes Activos', $activeClients)
                ->description('Con proyectos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Nuevos este Mes', $thisMonthClients)
                ->description('Registrados en ' . Carbon::now()->format('M'))
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('info'),
        ];
    }
}
