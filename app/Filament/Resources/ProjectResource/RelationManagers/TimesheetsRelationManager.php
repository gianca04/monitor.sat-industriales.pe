<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

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
            ->schema([Section::make('Datos del registro de asistencia')
                    ->description('Completa los detalles del check-in, break y check-out')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        // Sección de empleado

                        Forms\Components\Select::make('project_id')
                            ->label('Proyecto')
                            ->required()
                            ->searchable()
                            ->options(function (callable $get) {
                                $search = $get('search');
                                $sessionprojectId = session('project_id');
                                $query = Project::query()
                                    ->select('projects.id', 'projects.name')
                                    ->when($search, function ($query) use ($search) {
                                        $query->where('projects.name', 'like', "%{$search}%");
                                    })
                                    ->limit(10);

                                return $query->get()
                                    ->unique('id')
                                    ->mapWithKeys(function ($project) {
                                        $label = "{$project->name}";
                                        return [$project->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->default(fn() => session('project_id'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Validar si ya existe un tareo para este proyecto en la fecha seleccionada
                                $checkInDate = $get('check_in_date');
                                if ($state && $checkInDate) {
                                    $existingTimesheet = Timesheet::where('project_id', $state)
                                        ->whereDate('check_in_date', Carbon::parse($checkInDate)->toDateString())
                                        ->first();

                                    if ($existingTimesheet) {
                                        Notification::make()
                                            ->title('¡Atención!')
                                            ->body('Ya existe un tareo para este proyecto en la fecha seleccionada.')
                                            ->warning()
                                            ->send();
                                    }
                                }
                            })
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $checkInDate = request()->input('check_in_date');
                                        $recordId = request()->route('record'); // ID del registro actual (para edición)

                                        if ($value && $checkInDate) {
                                            $exists = static::validateUniqueTimesheetForProjectDate(
                                                $value,
                                                $checkInDate,
                                                $recordId
                                            );

                                            if ($exists) {
                                                $fail('Ya existe un tareo para este proyecto en la fecha seleccionada.');
                                            }
                                        }
                                    };
                                },
                            ])
                            ->required(),


                        Forms\Components\Select::make('employee_id')
                            ->required()
                            ->default(fn(callable $get) => Auth::user()?->employee_id)
                            ->columns(2)
                            ->prefixIcon('heroicon-m-user')
                            ->label('Responsable del Tareo') // Título para el campo 'Empleado'
                            ->options(
                                function (callable $get) {
                                    return Employee::query()
                                        ->select('id', 'first_name', 'last_name', 'document_number')
                                        ->when($get('search'), function ($query, $search) {
                                            $query->where('first_name', 'like', "%{$search}%")
                                                ->orWhere('last_name', 'like', "%{$search}%")
                                                ->orWhere('document_number', 'like', "%{$search}%");
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($employee) {
                                            return [$employee->id => $employee->full_name];
                                        })
                                        ->toArray();
                                }
                            )
                            ->searchable() // Activa la búsqueda asincrónica
                            ->placeholder('Seleccionar un supervisor') // Placeholder
                            ->reactive(),

                        // ...existing code...

                        DateTimePicker::make('check_in_date')
                            ->label('Fecha de entrada')
                            ->seconds(false)
                            ->default(now())
                            ->weekStartsOnMonday()
                            ->maxDate(fn(callable $get) => $get('check_out_date'))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                // Validar si ya existe un tareo para este proyecto en la fecha seleccionada
                                $projectId = $get('project_id');
                                if ($state && $projectId) {
                                    $existingTimesheet = Timesheet::where('project_id', $projectId)
                                        ->whereDate('check_in_date', Carbon::parse($state)->toDateString())
                                        ->first();

                                    if ($existingTimesheet) {
                                        Notification::make()
                                            ->title('¡Tareo duplicado!')
                                            ->body('Ya existe un tareo para este proyecto en la fecha seleccionada. No se puede crear otro tareo para el mismo día.')
                                            ->warning()
                                            ->persistent()
                                            ->send();
                                    }
                                }

                                $checkIn = $get('check_in_date');
                                if ($checkIn) {
                                    $in = Carbon::parse($checkIn)->format('H:i');
                                    // Turno noche si entra a las 18:00 o después
                                    if ($in >= '18:00') {
                                        $set('shift', 'night');
                                    } else {
                                        $set('shift', 'day');
                                    }
                                }
                            })
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $projectId = request()->input('project_id');
                                        $recordId = request()->route('record');

                                        if ($value && $projectId) {
                                            $exists = static::validateUniqueTimesheetForProjectDate(
                                                $projectId,
                                                $value,
                                                $recordId
                                            );

                                            if ($exists) {
                                                $fail('Ya existe un tareo para este proyecto en esta fecha.');
                                            }
                                        }
                                    };
                                },
                            ])
                            ->prefixIcon('heroicon-o-arrow-right-end-on-rectangle'),

                        DateTimePicker::make('break_date')
                            ->label('Inicio del descanso')
                            ->seconds(false)
                            ->default(fn(callable $get) => Carbon::parse($get('check_in_date'))->addHours(4)) // Parse check_in_date as Carbon and add 3 hours
                            ->prefixIcon('heroicon-o-pause')
                            ->minDate(fn(callable $get) => Carbon::parse($get('check_in_date'))), // Parse check_in_date as Carbon

                        DateTimePicker::make('end_break_date')
                            ->label('Fin del descanso')
                            ->seconds(false)
                            ->default(fn(callable $get) => Carbon::parse($get('break_date'))->addHours(1)) // Parse check_in_date as Carbon and add 3 hours
                            ->minDate(fn(callable $get) => Carbon::parse($get('break_date'))) // Parse check_in_date as Carbon
                            ->required()
                            ->prefixIcon('heroicon-o-play'),

                        DateTimePicker::make('check_out_date')
                            ->label('Fecha de salida')
                            ->seconds(false)
                            ->default(fn(callable $get) => Carbon::parse($get('check_out_date'))->addHours(9))
                            ->weekStartsOnMonday()
                            ->minDate(fn(callable $get) => $get('check_in_date'))
                            ->required()
                            ->prefixIcon('heroicon-o-arrow-right-start-on-rectangle')
                            ->reactive(),

                    ])
                    ->columns(2),

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
                    ->formatStateUsing(fn($state) => match ($state) {
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

                /*Tables\Columns\TextColumn::make('break_duration')
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
                    */

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => session('filter_project_id'))
                    ->placeholder('Todos los proyectos'),

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
                                fn(Builder $query, $date): Builder => $query->whereDate('check_in_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('check_in_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
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
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Editar en el formulario completo con todas las funcionalidades')
                    ->action(function ($record) {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource edit
                        return redirect(route('filament.dashboard.resources.timesheets.edit', $record));
                    }),

                /*Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Ver detalles del tareo')
                    ->modalWidth('3xl')
                    ->color('info'),
                */
                Tables\Actions\Action::make('Ver')
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
