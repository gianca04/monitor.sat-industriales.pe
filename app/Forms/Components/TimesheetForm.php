<?php

namespace App\Forms\Components;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TimesheetForm
{
    /**
     * Genera el esquema del formulario de timesheet
     */
    public static function getSchema(): array
    {
        return [
            Section::make('Datos del registro de asistencia')
                ->description('Completa los detalles del check-in, break y check-out')
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    // Proyecto
                    Forms\Components\Select::make('project_id')
                        ->label('Proyecto')
                        ->required()
                        ->searchable()
                        ->options(
                            function (callable $get) {
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
                            }
                        )
                        ->default(fn() => session('project_id'))
                        ->reactive()
                        ->afterStateHydrated(function ($state, callable $set) {
                            if ($state) {
                                $project = Project::find($state);
                                if ($project) {
                                    // Mantener el ID numérico, no el nombre
                                    $set('project_id', $project->id);
                                }
                            }
                        })
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
                                    $recordId = request()->route('record');

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

                    // Empleado responsable
                    Forms\Components\Select::make('employee_id')
                        ->required()
                        ->default(fn(callable $get) => Auth::user()?->employee_id)
                        ->columns(2)
                        ->prefixIcon('heroicon-m-user')
                        ->label('Responsable del Tareo')
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
                        ->searchable()
                        ->placeholder('Seleccionar un supervisor')
                        ->reactive(),

                    // Turno
                    Forms\Components\Select::make('shift')
                        ->label('Turno')
                        ->required()
                        ->options([
                            'day' => 'Diurno',
                            'night' => 'Nocturno',
                        ])
                        ->default('day')
                        ->live()
                        ->afterStateHydrated(function ($state, callable $set, callable $get) {
                            // Si no hay estado, usar el default
                            if (!$state) {
                                $state = 'day';
                            }
                            
                            // Obtener la fecha base, si no hay check_in_date usar hoy
                            $baseDate = $get('check_in_date') ? Carbon::parse($get('check_in_date')) : now();
                            
                            if ($state === 'night') {
                                // Turno nocturno: 22:00 PM a 06:00 AM (8 horas) - SIN DESCANSO
                                $checkInTime = $baseDate->copy()->setTime(22, 0, 0);
                                $checkOutTime = $checkInTime->copy()->addHours(8);
                                
                                // Limpiar campos de break para turno nocturno
                                if (!$get('check_in_date')) {
                                    $set('check_in_date', $checkInTime->format('Y-m-d H:i:s'));
                                }
                                if (!$get('check_out_date')) {
                                    $set('check_out_date', $checkOutTime->format('Y-m-d H:i:s'));
                                }
                                // NO establecer break_date ni end_break_date para turno nocturno
                                $set('break_date', null);
                                $set('end_break_date', null);
                            } else {
                                // Turno diurno: 08:00 AM a 17:00 PM (9 horas) - CON DESCANSO
                                $checkInTime = $baseDate->copy()->setTime(8, 0, 0);
                                $checkOutTime = $baseDate->copy()->setTime(17, 0, 0);
                                $breakTime = $checkInTime->copy()->addHours(4); // 12:00 PM
                                $endBreakTime = $breakTime->copy()->addHour(); // 13:00 PM
                                
                                // Actualizar todos los campos si no están ya establecidos
                                if (!$get('check_in_date')) {
                                    $set('check_in_date', $checkInTime->format('Y-m-d H:i:s'));
                                }
                                if (!$get('check_out_date')) {
                                    $set('check_out_date', $checkOutTime->format('Y-m-d H:i:s'));
                                }
                                if (!$get('break_date')) {
                                    $set('break_date', $breakTime->format('Y-m-d H:i:s'));
                                }
                                if (!$get('end_break_date')) {
                                    $set('end_break_date', $endBreakTime->format('Y-m-d H:i:s'));
                                }
                            }
                        })
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            // Obtener la fecha base, si no hay check_in_date usar hoy
                            $baseDate = $get('check_in_date') ? Carbon::parse($get('check_in_date')) : now();
                            
                            if ($state === 'night') {
                                // Turno nocturno: 22:00 PM a 06:00 AM (8 horas) - SIN DESCANSO
                                $checkInTime = $baseDate->copy()->setTime(22, 0, 0);
                                $checkOutTime = $checkInTime->copy()->addHours(8);
                                
                                $set('check_in_date', $checkInTime->format('Y-m-d H:i:s'));
                                $set('check_out_date', $checkOutTime->format('Y-m-d H:i:s'));
                                // Limpiar campos de break para turno nocturno
                                $set('break_date', null);
                                $set('end_break_date', null);
                            } else {
                                // Turno diurno: 08:00 AM a 17:00 PM (9 horas) - CON DESCANSO
                                $checkInTime = $baseDate->copy()->setTime(8, 0, 0);
                                $checkOutTime = $baseDate->copy()->setTime(17, 0, 0);
                                $breakTime = $checkInTime->copy()->addHours(4); // 12:00 PM
                                $endBreakTime = $breakTime->copy()->addHour(); // 13:00 PM
                                
                                // Actualizar todos los campos
                                $set('check_in_date', $checkInTime->format('Y-m-d H:i:s'));
                                $set('check_out_date', $checkOutTime->format('Y-m-d H:i:s'));
                                $set('break_date', $breakTime->format('Y-m-d H:i:s'));
                                $set('end_break_date', $endBreakTime->format('Y-m-d H:i:s'));
                            }
                        })
                        ->prefixIcon('heroicon-o-clock'),

                    // Fecha de entrada
                    DateTimePicker::make('check_in_date')
                        ->label('Fecha de entrada')
                        ->seconds(false)
                        ->default(now())
                        ->weekStartsOnMonday()
                        ->maxDate(fn(callable $get) => $get('check_out_date'))
                        ->live()
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

                    // Inicio del descanso
                    DateTimePicker::make('break_date')
                        ->label('Inicio del descanso')
                        ->seconds(false)
                        ->default(function (callable $get) {
                            $shift = $get('shift') ?? 'day';
                            if ($shift === 'night') {
                                return null; // No break para turno nocturno
                            }
                            
                            $checkInDate = $get('check_in_date');
                            if ($checkInDate) {
                                return Carbon::parse($checkInDate)->addHours(4);
                            }
                            return now()->addHours(4);
                        })
                        ->visible(fn (callable $get) => $get('shift') !== 'night') // Ocultar si es turno nocturno
                        ->live()
                        ->prefixIcon('heroicon-o-pause')
                        ->minDate(fn(callable $get) => $get('check_in_date')),

                    // Fin del descanso
                    DateTimePicker::make('end_break_date')
                        ->label('Fin del descanso')
                        ->seconds(false)
                        ->default(function (callable $get) {
                            $shift = $get('shift') ?? 'day';
                            if ($shift === 'night') {
                                return null; // No break para turno nocturno
                            }
                            
                            $breakDate = $get('break_date');
                            if ($breakDate) {
                                return Carbon::parse($breakDate)->addHour();
                            }
                            $checkInDate = $get('check_in_date');
                            if ($checkInDate) {
                                return Carbon::parse($checkInDate)->addHours(5);
                            }
                            return now()->addHours(5);
                        })
                        ->visible(fn (callable $get) => $get('shift') !== 'night') // Ocultar si es turno nocturno
                        ->live()
                        ->prefixIcon('heroicon-o-play')
                        ->minDate(fn(callable $get) => $get('break_date')),

                    // Fecha de salida
                    DateTimePicker::make('check_out_date')
                        ->label('Fecha de salida')
                        ->seconds(false)
                        ->default(function (callable $get) {
                            $shift = $get('shift') ?? 'day';
                            $checkInDate = $get('check_in_date') ? Carbon::parse($get('check_in_date')) : now();

                            if ($shift === 'night') {
                                // Turno nocturno: suma 8 horas desde las 22:00 = 06:00 del día siguiente
                                return $checkInDate->copy()->setTime(22, 0, 0)->addHours(8);
                            } else {
                                // Turno diurno: 17:00 del mismo día
                                return $checkInDate->copy()->setTime(17, 0, 0);
                            }
                        })
                        ->weekStartsOnMonday()
                        ->minDate(fn(callable $get) => $get('check_in_date'))
                        ->required()
                        ->live()
                        ->prefixIcon('heroicon-o-arrow-right-start-on-rectangle'),

                ])
                ->columns(2),
        ];
    }

    /**
     * Valida que no exista un tareo para el mismo proyecto en la misma fecha
     */
    public static function validateUniqueTimesheetForProjectDate($projectId, $checkInDate, $excludeId = null)
    {
        $query = Timesheet::where('project_id', $projectId)
            ->whereDate('check_in_date', Carbon::parse($checkInDate)->toDateString());

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
