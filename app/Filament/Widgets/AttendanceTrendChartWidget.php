<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class AttendanceTrendChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Tendencia de Asistencias (Últimos 7 días)';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $attendedData = [];
        $absentData = [];

        // Obtener datos de los últimos 7 días
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d/m');

            $dayAttendances = Attendance::whereHas('timesheet', function ($query) use ($date) {
                $query->whereDate('check_in_date', $date->toDateString());
            });

            $attended = $dayAttendances->where('status', 'attended')->count();
            $absent = $dayAttendances->where('status', 'absent')->count();

            $attendedData[] = $attended;
            $absentData[] = $absent;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Asistieron',
                    'data' => $attendedData,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                    'fill' => false,
                ],
                [
                    'label' => 'Faltaron',
                    'data' => $absentData,
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                    'fill' => false,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ];
    }
}
