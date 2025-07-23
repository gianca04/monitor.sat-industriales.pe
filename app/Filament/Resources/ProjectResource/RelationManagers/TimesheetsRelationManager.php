<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Employee;
use App\Models\Timesheet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class TimesheetsRelationManager extends RelationManager
{
    protected static string $relationship = 'timesheets';

    protected static ?string $title = 'Tareos';

    protected static ?string $modelLabel = 'tareo';

    protected static ?string $pluralModelLabel = 'tareos';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Tareo')
                    ->description('Registra los horarios de trabajo para este proyecto')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Responsable del Tareo')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-user')
                            ->options(function () {
                                return Employee::query()
                                    ->select('id', 'first_name', 'last_name', 'document_number')
                                    ->get()
                                    ->mapWithKeys(function ($employee) {
                                        return [$employee->id => $employee->first_name . ' ' . $employee->last_name . ' - ' . $employee->document_number];
                                    })
                                    ->toArray();
                            })
                            ->helperText('Selecciona el empleado para este tareo'),

                        Forms\Components\Select::make('shift')
                            ->label('Turno')
                            ->required()
                            ->prefixIcon('heroicon-o-sun')
                            ->options([
                                'morning' => 'Mañana (7:00 - 15:00)',
                                'afternoon' => 'Tarde (15:00 - 23:00)',
                                'night' => 'Noche (23:00 - 7:00)',
                                'full_day' => 'Día completo (7:00 - 18:00)',
                                'custom' => 'Horario personalizado',
                            ])
                            ->default('morning')
                            ->helperText('Selecciona el turno de trabajo'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Horarios de Trabajo')
                    ->description('Registra los horarios de entrada, descanso y salida')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        Forms\Components\DateTimePicker::make('check_in_date')
                            ->label('Hora de Entrada')
                            ->required()
                            ->prefixIcon('heroicon-o-arrow-right-on-rectangle')
                            ->default(now()->startOfDay()->addHours(7))
                            ->helperText('Fecha y hora de entrada al trabajo')
                            ->reactive()
                            ->afterStateUpdated(function (callable $get, callable $set, $state) {
                                // Auto-sugerir horarios basados en la entrada
                                if ($state && !$get('break_date')) {
                                    $checkIn = \Carbon\Carbon::parse($state);
                                    $set('break_date', $checkIn->copy()->addHours(4)); // Descanso después de 4 horas
                                    $set('end_break_date', $checkIn->copy()->addHours(4)->addMinutes(30)); // 30 min de descanso
                                    $set('check_out_date', $checkIn->copy()->addHours(8)); // 8 horas de trabajo
                                }
                            }),

                        Forms\Components\DateTimePicker::make('check_out_date')
                            ->label('Hora de Salida')
                            ->prefixIcon('heroicon-o-arrow-left-on-rectangle')
                            ->helperText('Fecha y hora de salida del trabajo')
                            ->after('check_in_date'),

                        Forms\Components\DateTimePicker::make('break_date')
                            ->label('Inicio de Descanso')
                            ->prefixIcon('heroicon-o-pause')
                            ->helperText('Hora de inicio del descanso')
                            ->after('check_in_date')
                            ->before('end_break_date'),

                        Forms\Components\DateTimePicker::make('end_break_date')
                            ->label('Fin de Descanso')
                            ->prefixIcon('heroicon-o-play')
                            ->helperText('Hora de fin del descanso')
                            ->after('break_date')
                            ->before('check_out_date'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Resumen del Tareo')
                    ->description('Información calculada automáticamente')
                    ->icon('heroicon-o-calculator')
                    ->schema([
                        Forms\Components\Placeholder::make('work_summary')
                            ->label('Resumen de Horas')
                            ->content(function (callable $get) {
                                $checkIn = $get('check_in_date');
                                $checkOut = $get('check_out_date');
                                $breakStart = $get('break_date');
                                $breakEnd = $get('end_break_date');

                                if (!$checkIn || !$checkOut) {
                                    return 'Ingresa las horas de entrada y salida para ver el resumen';
                                }

                                $start = \Carbon\Carbon::parse($checkIn);
                                $end = \Carbon\Carbon::parse($checkOut);
                                $totalMinutes = $end->diffInMinutes($start);

                                // Restar tiempo de descanso si está definido
                                if ($breakStart && $breakEnd) {
                                    $breakStartTime = \Carbon\Carbon::parse($breakStart);
                                    $breakEndTime = \Carbon\Carbon::parse($breakEnd);
                                    $breakMinutes = $breakEndTime->diffInMinutes($breakStartTime);
                                    $totalMinutes -= $breakMinutes;
                                }

                                $hours = floor($totalMinutes / 60);
                                $minutes = $totalMinutes % 60;

                                return "Total trabajado: {$hours} horas y {$minutes} minutos";
                            }),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Responsable del Tareo')
                    ->formatStateUsing(function ($record) {
                        return $record->employee ?
                            $record->employee->first_name . ' ' . $record->employee->last_name :
                            'Sin empleado';
                    })
                    ->searchable(['first_name', 'last_name'])
                    ->sortable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\BadgeColumn::make('shift')
                    ->label('Turno')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'morning' => 'Mañana',
                        'afternoon' => 'Tarde',
                        'night' => 'Noche',
                        'full_day' => 'Día Completo',
                        'custom' => 'Personalizado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'morning',
                        'primary' => 'afternoon',
                        'secondary' => 'night',
                        'success' => 'full_day',
                        'info' => 'custom',
                    ])
                    ->icons([
                        'heroicon-o-sun' => 'morning',
                        'heroicon-o-moon' => 'night',
                        'heroicon-o-clock' => ['afternoon', 'full_day', 'custom'],
                    ]),

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success'),

                Tables\Columns\TextColumn::make('check_out_date')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('break_duration')
                    ->label('Descanso')
                    ->formatStateUsing(function ($record) {
                        if (!$record->break_date || !$record->end_break_date) {
                            return 'Sin descanso';
                        }

                        $start = \Carbon\Carbon::parse($record->break_date);
                        $end = \Carbon\Carbon::parse($record->end_break_date);
                        $minutes = $end->diffInMinutes($start);

                        return "{$minutes} min";
                    })
                    ->icon('heroicon-o-pause')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Horas Totales')
                    ->formatStateUsing(function ($record) {
                        if (!$record->check_in_date || !$record->check_out_date) {
                            return 'Incompleto';
                        }

                        $start = \Carbon\Carbon::parse($record->check_in_date);
                        $end = \Carbon\Carbon::parse($record->check_out_date);
                        $totalMinutes = $end->diffInMinutes($start);

                        // Restar tiempo de descanso
                        if ($record->break_date && $record->end_break_date) {
                            $breakStart = \Carbon\Carbon::parse($record->break_date);
                            $breakEnd = \Carbon\Carbon::parse($record->end_break_date);
                            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
                            $totalMinutes -= $breakMinutes;
                        }

                        $hours = floor($totalMinutes / 60);
                        $minutes = $totalMinutes % 60;

                        return "{$hours}h {$minutes}m";
                    })
                    ->icon('heroicon-o-clock')
                    ->color('primary')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shift')
                    ->label('Turno')
                    ->options([
                        'morning' => 'Mañana',
                        'afternoon' => 'Tarde',
                        'night' => 'Noche',
                        'full_day' => 'Día Completo',
                        'custom' => 'Personalizado',
                    ]),

                Tables\Filters\SelectFilter::make('employee_id')
                    ->label('Empleado')
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('check_in_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                /*Tables\Actions\CreateAction::make()
                    ->label('Nuevo Tareo (Modal)')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear nuevo tareo')
                    ->modalWidth('4xl')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Tareo creado')
                            ->body('El tareo ha sido registrado exitosamente.')
                    )
                    ->color('gray'),
                  */
                Tables\Actions\Action::make('create_advanced')
                    ->label('Crear Tareo')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->tooltip('Ir al formulario completo de tareos con todas las funcionalidades')
                    ->action(function () {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource create
                        return redirect(route('filament.dashboard.resources.timesheets.create'));
                    }),

                Tables\Actions\Action::make('manage_all')
                    ->label('Gestionar Todos los Tareos')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->tooltip('Ver y gestionar todos los tareos del proyecto en la vista completa')
                    ->action(function () {
                        // Guardar el project_id en la sesión para filtros
                        session(['filter_project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource index
                        return redirect(route('filament.dashboard.resources.timesheets.index'));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar (Modal)')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar tareo')
                    ->modalWidth('4xl')
                    ->color('gray'),

                Tables\Actions\Action::make('edit_advanced')
                    ->label('Editar Completo')
                    ->icon('heroicon-o-document-text')
                    ->color('primary')
                    ->tooltip('Editar en el formulario completo con todas las funcionalidades')
                    ->action(function ($record) {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource edit
                        return redirect(route('filament.dashboard.resources.timesheets.edit', $record));
                    }),

                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Ver detalles del tareo')
                    ->modalWidth('3xl')
                    ->color('info'),

                Tables\Actions\Action::make('view_advanced')
                    ->label('Ver Completo')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver en la vista completa con todas las relaciones')
                    ->action(function ($record) {
                        // Redirigir al TimesheetResource view
                        return redirect(route('filament.dashboard.resources.timesheets.view', $record));
                    }),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('check_in_date', 'desc')
            ->emptyStateHeading('No hay tareos registrados')
            ->emptyStateDescription('Comienza creando el primer tareo para este proyecto.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
