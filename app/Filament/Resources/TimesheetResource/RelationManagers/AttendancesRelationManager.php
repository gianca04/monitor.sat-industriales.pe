<?php

namespace App\Filament\Resources\TimesheetResource\RelationManagers;

use App\Models\Employee;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Imports\AttendancesImport;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class AttendancesRelationManager extends RelationManager
{
    use Translatable;
    protected static string $relationship = 'attendances';

    protected static ?string $pluralModelLabel = 'Asistencias';

    protected static ?string $modelLabel = 'Asistencia';
    protected static ?string $title = 'Asistencias'; // Cambia el título de la tabla aquí


    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->required()
                            ->prefixIcon('heroicon-m-user')
                            ->label('Empleado')
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
                            ->placeholder('Seleccionar un colaborador')
                            ->reactive()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $timesheetId = request()->route('record');
                                        if ($value && $timesheetId) {
                                            $exists = \App\Models\Attendance::where('timesheet_id', $timesheetId)
                                                ->where('employee_id', $value)
                                                ->exists();
                                            if ($exists) {
                                                $fail('Este empleado ya tiene una asistencia registrada en este tareo.');
                                            }
                                        }
                                    };
                                },
                            ]),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'attended' => 'Asistió',
                                'present' => 'Presente',
                                'late' => 'Llegó Tarde',
                                'absent' => 'Faltó',
                                'justified' => 'Justificado'
                            ])
                            ->default('attended')
                            ->native(false)
                            ->reactive()
                            ->prefixIcon('heroicon-o-check-circle'),
                    ]),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Select::make('shift')
                            ->label('Turno')
                            ->options([
                                'day' => 'Día',
                                'night' => 'Noche',
                            ])
                            ->default('day')
                            ->native(false)
                            ->prefixIcon('heroicon-o-clock'),


                    ]),

                Forms\Components\Section::make('Horarios de Trabajo')
                    ->description('Complete los horarios solo si el empleado asistió')
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->collapsible()
                    ->visible(fn(callable $get) => in_array($get('status'), ['attended', 'present', 'late']))
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                DateTimePicker::make('check_in_date')
                                    ->label('Fecha de entrada')
                                    ->seconds(false)
                                    ->weekStartsOnMonday()
                                    ->default(fn() => $this->getOwnerRecord()?->check_in_date)
                                    ->prefixIcon('heroicon-o-arrow-right-end-on-rectangle'),

                                DateTimePicker::make('check_out_date')
                                    ->label('Fecha de salida')
                                    ->seconds(false)
                                    ->after('check_in_date')
                                    ->default(fn() => $this->getOwnerRecord()?->check_out_date)
                                    ->prefixIcon('heroicon-o-arrow-right-start-on-rectangle'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                DateTimePicker::make('break_date')
                                    ->label('Inicio del descanso')
                                    ->seconds(false)
                                    ->after('check_in_date')
                                    ->before('end_break_date')
                                    ->prefixIcon('heroicon-o-pause'),

                                DateTimePicker::make('end_break_date')
                                    ->label('Fin del descanso')
                                    ->seconds(false)
                                    ->after('break_date')
                                    ->before('check_out_date')
                                    ->prefixIcon('heroicon-o-play'),
                            ]),
                    ]),

                Forms\Components\Textarea::make('observation')
                    ->label('Observaciones')
                    ->placeholder('Comentarios adicionales sobre la asistencia...')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('employee_id')
            ->columns([
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Empleado')
                    ->searchable(['first_name', 'last_name', 'document_number'])
                    ->sortable()
                    ->wrap(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Estado')
                    ->options([
                        'attended' => 'Asistió',
                        'absent' => 'Faltó',
                        'justified' => 'Justificado',
                        'present' => 'Presente',
                        'late' => 'Llegó Tarde'
                    ])
                    ->sortable()
                    ->searchable()
                    ->afterStateUpdated(function ($record, $state) {
                        if (in_array($state, ['attended', 'present'])) {
                            $record->update([
                                'status' => $state,
                                'check_in_date' => $record->timesheet->check_in_date,
                            ]);
                        } elseif (in_array($state, ['absent', 'justified', 'late'])) {
                            $updates = ['status' => $state];

                            // Solo limpiar horarios si es ausente o justificado, no si llegó tarde
                            if (in_array($state, ['absent', 'justified'])) {
                                $updates = array_merge($updates, [
                                    'check_in_date' => null,
                                    'break_date' => null,
                                    'end_break_date' => null,
                                    'check_out_date' => null,
                                ]);
                            }

                            $record->update($updates);
                        }
                    })
                    ->beforeStateUpdated(function ($record, $state) {
                        // Validación adicional antes de cambiar estado
                        if (in_array($state, ['attended', 'present', 'late']) && !$record->timesheet) {
                            throw new \Exception('No se puede marcar como asistido sin un tareo válido.');
                        }
                    }),

                Tables\Columns\BadgeColumn::make('shift')
                    ->label('Turno')
                    ->colors([
                        'primary' => 'day',
                        'warning' => 'night',
                    ])
                    ->icons([
                        'heroicon-o-sun' => 'day',
                        'heroicon-o-moon' => 'night',
                    ])
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'day' => 'Día',
                        'night' => 'Noche',
                        null => 'No definido',
                        default => $state,
                    })
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('NO REGISTRADO')
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->weight(fn($state) => $state ? 'medium' : 'light'),

                Tables\Columns\TextColumn::make('work_duration')
                    ->label('Horas Trabajadas')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->check_in_date || !$record->check_out_date) {
                            return null;
                        }

                        $checkIn = Carbon::parse($record->check_in_date);
                        $checkOut = Carbon::parse($record->check_out_date);

                        // Calcular tiempo total en minutos
                        $totalMinutes = $checkIn->diffInMinutes($checkOut);

                        // Calcular tiempo de break en minutos
                        $breakTime = 0;
                        if ($record->break_date && $record->end_break_date) {
                            $breakStart = Carbon::parse($record->break_date);
                            $breakEnd = Carbon::parse($record->end_break_date);
                            $breakTime = $breakStart->diffInMinutes($breakEnd);
                        }

                        // Tiempo trabajado = tiempo total - tiempo de break
                        $workedMinutes = max(0, $totalMinutes - $breakTime);

                        if ($workedMinutes <= 0) {
                            return null;
                        }

                        $hours = intval($workedMinutes / 60);
                        $minutes = $workedMinutes % 60;

                        return "{$hours}h {$minutes}m";
                    })
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'gray')
                    ->placeholder('NO CALCULADO')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('extra_hours')
                    ->label('Horas Extra')
                    ->getStateUsing(function ($record) {
                        if (!$record || !$record->check_in_date || !$record->check_out_date || !$record->timesheet) {
                            return null;
                        }

                        $checkIn = Carbon::parse($record->check_in_date);
                        $checkOut = Carbon::parse($record->check_out_date);

                        // Calcular tiempo total trabajado por el empleado
                        $totalMinutes = $checkIn->diffInMinutes($checkOut);

                        // Calcular tiempo de break del empleado
                        $breakTime = 0;
                        if ($record->break_date && $record->end_break_date) {
                            $breakStart = Carbon::parse($record->break_date);
                            $breakEnd = Carbon::parse($record->end_break_date);
                            $breakTime = $breakStart->diffInMinutes($breakEnd);
                        }

                        // Tiempo trabajado por el empleado
                        $totalWorkedMinutes = max(0, $totalMinutes - $breakTime);

                        // Obtener horarios del timesheet (horario estándar)
                        $timesheet = $record->timesheet;
                        if (!$timesheet->check_in_date || !$timesheet->check_out_date) {
                            return 'Sin horario base';
                        }

                        $timesheetCheckIn = Carbon::parse($timesheet->check_in_date);
                        $timesheetCheckOut = Carbon::parse($timesheet->check_out_date);

                        // Calcular tiempo total del horario estándar
                        $standardTotalMinutes = $timesheetCheckIn->diffInMinutes($timesheetCheckOut);

                        // Calcular tiempo de break del timesheet
                        $timesheetBreakTime = 0;
                        if ($timesheet->break_date && $timesheet->end_break_date) {
                            $timesheetBreakStart = Carbon::parse($timesheet->break_date);
                            $timesheetBreakEnd = Carbon::parse($timesheet->end_break_date);
                            $timesheetBreakTime = $timesheetBreakStart->diffInMinutes($timesheetBreakEnd);
                        } else {
                            // Si el timesheet no tiene break configurado, pero los empleados sí,
                            // asumimos un break estándar de 1 hora (60 minutos)
                            $timesheetBreakTime = 60;
                        }

                        // Minutos de trabajo estándar según el timesheet
                        $standardWorkMinutes = max(0, $standardTotalMinutes - $timesheetBreakTime);

                        // Calcular horas extra
                        $extraMinutes = max(0, $totalWorkedMinutes - $standardWorkMinutes);

                        if ($extraMinutes > 0) {
                            $extraHours = intval($extraMinutes / 60);
                            $extraMinutesRemainder = $extraMinutes % 60;
                            return "{$extraHours}h {$extraMinutesRemainder}m";
                        }

                        return '0h 0m';
                    })
                    ->badge()
                    ->color(function ($state) {
                        if (!$state || $state === '0h 0m' || $state === 'Sin horario base') {
                            return 'gray';
                        }
                        return 'warning'; // Color naranja para horas extra
                    })
                    ->placeholder('NO CALCULADO')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('break_date')
                    ->label('Inicio descanso')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('NO REGISTRADO'),

                Tables\Columns\TextColumn::make('end_break_date')
                    ->label('Fin descanso')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('NO REGISTRADO'),

                Tables\Columns\TextColumn::make('check_out_date')
                    ->label('Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('NO REGISTRADO')
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                Tables\Columns\TextInputColumn::make('observation')
                    ->label('Observación')
                    ->placeholder('Agregar observación...')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'attended' => 'Asistió',
                        'present' => 'Presente',
                        'late' => 'Llegó Tarde',
                        'absent' => 'Faltó',
                        'justified' => 'Justificado'
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('shift')
                    ->label('Turno')
                    ->options([
                        'day' => 'Día',
                        'night' => 'Noche',
                    ]),

                Tables\Filters\Filter::make('has_full_schedule')
                    ->label('Horario Completo')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('check_in_date')->whereNotNull('check_out_date'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_break')
                    ->label('Con Descanso')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('break_date')->whereNotNull('end_break_date'))
                    ->toggle(),
            ])
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('marcarComoAsistio')
                        ->label('Marcar como Asistió')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $attendance) {
                                if ($attendance && $attendance->timesheet) {
                                    $attendance->update([
                                        'status' => 'attended',
                                        'check_in_date' => $attendance->timesheet->check_in_date,
                                    ]);
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('marcarComoFalto')
                        ->label('Marcar como Faltó')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn($records) => $records->each->update([
                            'status' => 'absent',
                            'check_in_date' => null,
                            'break_date' => null,
                            'end_break_date' => null,
                            'check_out_date' => null,
                        ])),
                    Tables\Actions\BulkAction::make('marcarComoJustificado')
                        ->label('Marcar como Justificado')
                        ->icon('heroicon-o-exclamation-circle')
                        ->action(fn($records) => $records->each->update(['status' => 'justified'])),
                    Tables\Actions\BulkAction::make('marcarInicioBreak')
                        ->label('Marcar inicio de break')
                        ->icon('heroicon-o-clock')
                        ->form([
                            Forms\Components\DateTimePicker::make('break_date')
                                ->label('Hora de inicio de break')
                                ->default(now())
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $attendance) {
                                $attendance->update([
                                    'break_date' => $data['break_date'],
                                ]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('marcarFinBreak')
                        ->label('Marcar fin de break')
                        ->icon('heroicon-o-clock')
                        ->form([
                            Forms\Components\DateTimePicker::make('end_break_date')
                                ->label('Hora de fin de break')
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $attendance) {
                                $endBreak = $data['end_break_date'] ?? $attendance->timesheet->end_break_date ?? now();
                                $attendance->update([
                                    'end_break_date' => $endBreak,
                                ]);
                            }
                        }),
                    Tables\Actions\BulkAction::make('marcarSalida')
                        ->label('Marcar salida')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            foreach ($records as $attendance) {
                                if ($attendance && $attendance->timesheet) {
                                    $attendance->update([
                                        'check_out_date' => $attendance->timesheet->check_out_date,
                                    ]);
                                }
                            }
                        }),
                ]),
            ]);
    }
}
