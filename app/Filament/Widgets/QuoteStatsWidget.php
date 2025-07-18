<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class QuoteStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalQuotes = Quote::count();
        $pendingQuotes = Quote::where('status', 'pending')->count();
        $approvedQuotes = Quote::where('status', 'approved')->count();

        return [
            Stat::make('Total Cotizaciones', $totalQuotes)
                ->description('Cotizaciones registradas')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make('Pendientes', $pendingQuotes)
                ->description('Estado pendiente')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Aprobadas', $approvedQuotes)
                ->description('Estado aprobado')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
