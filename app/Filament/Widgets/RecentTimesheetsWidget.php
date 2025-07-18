<?php

namespace App\Filament\Widgets;

use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTimesheetsWidget extends BaseWidget
{
    protected static ?string $heading = 'Tareos Recientes';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Timesheet::with(['project', 'employee'])
                    ->latest('check_in_date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        $date = Carbon::parse($record->check_in_date);
                        if ($date->isToday()) return 'success';
                        if ($date->isYesterday()) return 'warning';
                        return 'gray';
                    }),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Supervisor')
                    ->limit(25),

                Tables\Columns\BadgeColumn::make('shift')
                    ->label('Turno')
                    ->colors([
                        'success' => 'day',
                        'info' => 'night',
                    ])
                    ->icons([
                        'heroicon-o-sun' => 'day',
                        'heroicon-o-moon' => 'night',
                    ])
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'day' => 'Día',
                        'night' => 'Noche',
                        null => 'No definido',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->label('Asistencias')
                    ->counts('attendances')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        $total = $record->attendances()->count();
                        if ($total === 0) return 'Sin registros';

                        $attended = $record->attendances()->where('status', 'attended')->count();
                        $percentage = round(($attended / $total) * 100);

                        return "{$attended}/{$total} ({$percentage}%)";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $total = $record->attendances()->count();
                        if ($total === 0) return 'gray';

                        $attended = $record->attendances()->where('status', 'attended')->count();
                        $percentage = ($attended / $total) * 100;

                        if ($percentage >= 90) return 'success';
                        if ($percentage >= 70) return 'warning';
                        return 'danger';
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Timesheet $record): string =>
                        \App\Filament\Resources\TimesheetResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->emptyStateHeading('No hay tareos registrados')
            ->emptyStateDescription('Crea tu primer tareo para comenzar.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
