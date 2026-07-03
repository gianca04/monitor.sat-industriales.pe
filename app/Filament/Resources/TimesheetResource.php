<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimesheetResource\Pages;
use App\Filament\Resources\TimesheetResource\RelationManagers\AttendancesRelationManager;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Repeater;
use Filament\Notifications\Notification;
use App\Models\Attendance;
use App\Forms\Components\TimesheetForm;
use Illuminate\Support\Facades\DB;

class TimesheetResource extends Resource
{

    use Translatable;

    protected static ?string $modelLabel = 'Tareo';
    protected static ?string $pluralModelLabel = 'Tareos';

    protected static ?string $model = Timesheet::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';

    protected static ?string $navigationGroup = 'Control de operaciones';

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

    public static function form(Form $form): Form
    {
        return $form->schema(TimesheetForm::getSchema());
    }    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                Tables\Columns\TextColumn::make('check_in_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->description(function ($record) {
                        if (!$record || !$record->project_id || !$record->check_in_date) {
                            return '';
                        }

                        $sameDay = Timesheet::where('project_id', $record->project_id)
                            ->whereDate('check_in_date', Carbon::parse($record->check_in_date)->toDateString())
                            ->where('id', '!=', $record->id)
                            ->count();

                        return $sameDay > 0 ? 'Conflicto detectado' : 'Único del día';
                    })
                    ->color(function ($record) {
                        if (!$record || !$record->project_id || !$record->check_in_date) {
                            return 'gray';
                        }

                        $sameDay = Timesheet::where('project_id', $record->project_id)
                            ->whereDate('check_in_date', Carbon::parse($record->check_in_date)->toDateString())
                            ->where('id', '!=', $record->id)
                            ->count();
                        return $sameDay > 0 ? 'danger' : 'success';
                    }),

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
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'day' => 'Día',
                        'night' => 'Noche',
                        null => 'No definido',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Supervisor')
                    ->searchable(['first_name', 'last_name', 'document_number'])
                    ->sortable()
                    ->toggleable(),

                /*Tables\Columns\TextColumn::make('attendances_summary')
                    ->label('Resumen Asistencias')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if (!$record) {
                            return 'Sin datos';
                        }

                        $total = $record->attendances()->count();
                        $attended = $record->attendances()->where('status', 'attended')->count();
                        $absent = $record->attendances()->where('status', 'absent')->count();
                        $justified = $record->attendances()->where('status', 'justified')->count();

                        if ($total === 0) return 'Sin registros';

                        return "✅ {$attended} | ❌ {$absent} | ⚠️ {$justified} | Total: {$total}";
                    })
                    ->html()
                    ->searchable(false,
                */
                Tables\Columns\TextColumn::make('attended_count')
                    ->label('Asistió')
                    ->badge()
                    ->icon('heroicon-o-check-circle')

                    ->getStateUsing(fn($record) => $record->attendances()->where('status', 'attended')->count())
                    ->sortable(),

                Tables\Columns\TextColumn::make('absent_count')
                    ->label('Faltó')
                    ->badge()
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')

                    ->getStateUsing(fn($record) => $record->attendances()->where('status', 'absent')->count())
                    ->sortable(),

                Tables\Columns\TextColumn::make('justified_count')
                    ->label('Justificado')
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-exclamation-circle')

                    ->getStateUsing(fn($record) => $record->attendances()->where('status', 'justified')->count())
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_total')
                    ->label('Total')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(fn($record) => $record->attendances()->count())
                    ->sortable(),

                Tables\Columns\TextColumn::make('schedule_info')
                    ->label('Horario')
                    ->icon('heroicon-o-clock')
                    ->getStateUsing(function ($record) {
                        if (!$record) {
                            return '--:-- - --:--';
                        }

                        $checkIn = $record->check_in_date ? Carbon::parse($record->check_in_date)->format('H:i') : '--:--';
                        $checkOut = $record->check_out_date ? Carbon::parse($record->check_out_date)->format('H:i') : '--:--';

                        return "{$checkIn} - {$checkOut}";
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
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

                Tables\Filters\Filter::make('hoy')
                    ->label('Tareos de hoy')
                    ->query(fn(Builder $query): Builder => $query->whereDate('check_in_date', now()->toDateString()))
                    ->toggle(),

                Tables\Filters\Filter::make('conflictos')
                    ->label('Posibles conflictos')
                    ->query(function (Builder $query): Builder {
                        return $query->whereExists(function ($subQuery) {
                            $subQuery->select(DB::raw(1))
                                ->from('timesheets as t2')
                                ->whereColumn('t2.project_id', 'timesheets.project_id')
                                ->whereRaw('DATE(t2.check_in_date) = DATE(timesheets.check_in_date)')
                                ->whereColumn('t2.id', '!=', 'timesheets.id');
                        });
                    })
                    ->toggle(),
            ])
            ->actions([

                Tables\Actions\Action::make('exportAttendances')
                    ->label('Exportar Asistencias')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function ($record) {
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\AttendancesExport($record->id),
                            'asistencias_' . $record->project->name . '_' . $record->check_in_date->format('Y-m-d') . '.xlsx'
                        );
                    })
                    ->visible(fn($record) => $record->attendances()->count() > 0),
                Tables\Actions\Action::make('goto_project')
                    ->label('Ver Proyecto')
                    ->icon('heroicon-o-puzzle-piece')
                    ->color('info')
                    ->tooltip('Ir al proyecto de este tareo')
                    ->action(function ($record) {
                        if ($record->project_id) {
                            return redirect(route('filament.dashboard.resources.projects.edit', $record->project_id));
                        }
                    })
                    ->visible(fn($record) => $record->project_id !== null),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make(name: 'generarAsistencias')
                    ->label('Generar Asistencias')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\Repeater::make('empleados')
                            ->label('Empleados para generar asistencias')
                            ->schema([
                                Forms\Components\Select::make('employee_id')
                                    ->label('Empleado')
                                    ->searchable()
                                    ->options(function () {
                                        return Employee::query()
                                            ->select('id', 'first_name', 'last_name', 'document_number')
                                            ->get()
                                            ->mapWithKeys(function ($employee) {
                                                return [$employee->id => "{$employee->full_name} - {$employee->document_number}"];
                                            })
                                            ->toArray();
                                    })
                                    ->required()
                                    ->distinct()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('status')
                                    ->label('Estado inicial')
                                    ->options([
                                        'attended' => 'Asistió',
                                        'absent' => 'Faltó',
                                        'justified' => 'Justificado'
                                    ])
                                    ->default('attended')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar empleado')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['employee_id'] ? Employee::find($state['employee_id'])?->full_name : 'Nuevo empleado'
                            ),
                    ])
                    ->action(function (array $data, $record) {
                        $createdCount = 0;
                        $duplicatedCount = 0;

                        foreach ($data['empleados'] as $empleadoData) {
                            // Verificar si ya existe una asistencia para este empleado en este timesheet
                            $existingAttendance = Attendance::where([
                                'timesheet_id' => $record->id,
                                'employee_id' => $empleadoData['employee_id']
                            ])->first();

                            if ($existingAttendance) {
                                $duplicatedCount++;
                                continue;
                            }

                            // Crear la asistencia
                            $attendanceData = [
                                'timesheet_id' => $record->id,
                                'employee_id' => $empleadoData['employee_id'],
                                'status' => $empleadoData['status'],
                                'shift' => $record->shift,
                            ];

                            // Si el estado es 'attended', asignar las fechas del timesheet
                            if ($empleadoData['status'] === 'attended') {
                                $attendanceData['check_in_date'] = $record->check_in_date;
                                $attendanceData['break_date'] = $record->break_date;
                                $attendanceData['end_break_date'] = $record->end_break_date;
                                $attendanceData['check_out_date'] = $record->check_out_date;
                            }

                            Attendance::create($attendanceData);
                            $createdCount++;
                        }

                        // Mostrar notificación con resultados
                        $message = "Se crearon {$createdCount} asistencias correctamente.";
                        if ($duplicatedCount > 0) {
                            $message .= " {$duplicatedCount} empleados ya tenían asistencia registrada.";
                        }

                        Notification::make()
                            ->title('Asistencias generadas')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generar asistencias masivamente')
                    ->modalDescription('Selecciona los empleados para crear asistencias en este tareo.')
                    ->modalSubmitActionLabel('Generar asistencias'),
                /*Tables\Actions\Action::make('buscarYGenerarAsistencias')
                    ->label('Búsqueda Individual de Empleados')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->form([
                        Forms\Components\TextInput::make('search_term')
                            ->label('Buscar por DNI, nombre o apellido')
                            ->placeholder('Ingresa DNI, nombre o apellido...')
                            ->required()
                            ->minLength(2)
                            ->helperText('Ingresa al menos 2 caracteres para buscar empleados')
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (strlen($state) >= 2) {
                                    $employees = Employee::query()
                                        ->where('document_number', 'like', "%{$state}%")
                                        ->orWhere('first_name', 'like', "%{$state}%")
                                        ->orWhere('last_name', 'like', "%{$state}%")
                                        ->limit(20)
                                        ->get()
                                        ->map(function ($employee) {
                                            return [
                                                'employee_id' => $employee->id,
                                                'status' => 'attended',
                                                'selected' => false
                                            ];
                                        })
                                        ->toArray();

                                    $set('empleados_encontrados', $employees);

                                    if (count($employees) === 0) {
                                        Notification::make()
                                            ->title('Sin resultados')
                                            ->body('No se encontraron empleados con ese criterio de búsqueda.')
                                            ->warning()
                                            ->send();
                                    }
                                } else {
                                    $set('empleados_encontrados', []);
                                }
                            }),

                        Forms\Components\Repeater::make('empleados_encontrados')
                            ->label('Empleados encontrados')
                            ->hidden(fn(callable $get) => empty($get('empleados_encontrados')))
                            ->schema([
                                Forms\Components\Hidden::make('employee_id'),

                                Forms\Components\Placeholder::make('employee_info')
                                    ->label('Empleado')
                                    ->content(function (callable $get) {
                                        $employeeId = $get('employee_id');
                                        if ($employeeId) {
                                            $employee = Employee::find($employeeId);
                                            return $employee ? "{$employee->full_name} - {$employee->document_number}" : 'Empleado no encontrado';
                                        }
                                        return 'Sin seleccionar';
                                    }),

                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'attended' => 'Asistió',
                                        'absent' => 'Faltó',
                                        'justified' => 'Justificado'
                                    ])
                                    ->default('attended')
                                    ->required(),

                                Forms\Components\Checkbox::make('selected')
                                    ->label('Incluir')
                                    ->default(false),
                            ])
                            ->columns(4)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false),

                        Forms\Components\Placeholder::make('search_help')
                            ->label('')
                            ->content('💡 Realiza una búsqueda arriba para encontrar empleados.')
                            ->visible(fn(callable $get) => empty($get('empleados_encontrados')) && empty($get('search_term'))),

                        Forms\Components\Placeholder::make('no_selection_warning')
                            ->label('')
                            ->content('⚠️ Selecciona al menos un empleado marcando la casilla "Incluir".')
                            ->visible(fn(callable $get) => !empty($get('empleados_encontrados'))),
                    ])
                    ->action(function (array $data, $record) {
                        // Validar que se haya realizado una búsqueda
                        if (empty($data['search_term']) || strlen($data['search_term']) < 2) {
                            Notification::make()
                                ->title('Búsqueda requerida')
                                ->body('Debes realizar una búsqueda de empleados antes de generar asistencias.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Validar que haya empleados encontrados
                        if (empty($data['empleados_encontrados'])) {
                            Notification::make()
                                ->title('Sin empleados')
                                ->body('No se encontraron empleados con los criterios de búsqueda.')
                                ->warning()
                                ->send();
                            return;
                        }

                        // Validar que al menos un empleado esté seleccionado
                        $selectedEmployees = collect($data['empleados_encontrados'])
                            ->filter(fn($emp) => $emp['selected'] ?? false);

                        if ($selectedEmployees->isEmpty()) {
                            Notification::make()
                                ->title('Selección requerida')
                                ->body('Debes seleccionar al menos un empleado marcando la casilla "Incluir".')
                                ->warning()
                                ->send();
                            return;
                        }

                        $createdCount = 0;
                        $duplicatedCount = 0;

                        foreach ($data['empleados_encontrados'] as $empleadoData) {
                            if (!($empleadoData['selected'] ?? false)) {
                                continue;
                            }

                            // Verificar duplicados
                            $existingAttendance = Attendance::where([
                                'timesheet_id' => $record->id,
                                'employee_id' => $empleadoData['employee_id']
                            ])->first();

                            if ($existingAttendance) {
                                $duplicatedCount++;
                                continue;
                            }

                            // Crear asistencia
                            $attendanceData = [
                                'timesheet_id' => $record->id,
                                'employee_id' => $empleadoData['employee_id'],
                                'status' => $empleadoData['status'],
                                'shift' => $record->shift,
                            ];

                            if ($empleadoData['status'] === 'attended') {
                                $attendanceData['check_in_date'] = $record->check_in_date;
                                $attendanceData['break_date'] = $record->break_date;
                                $attendanceData['end_break_date'] = $record->end_break_date;
                                $attendanceData['check_out_date'] = $record->check_out_date;
                            }

                            Attendance::create($attendanceData);
                            $createdCount++;
                        }

                        $message = "Se crearon {$createdCount} asistencias correctamente.";
                        if ($duplicatedCount > 0) {
                            $message .= " {$duplicatedCount} empleados ya tenían asistencia registrada.";
                        }

                        Notification::make()
                            ->title('Asistencias generadas')
                            ->body($message)
                            ->success()
                            ->send();
                    })
                    ->modalHeading('Búsqueda avanzada de empleados')
                    ->modalWidth('7xl'),
                */
            ])
            ->headerActions([

                Tables\Actions\Action::make('back_to_project')
                    ->label('Volver al Proyecto')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->visible(fn() => session()->has('project_id'))
                    ->action(function () {
                        $projectId = session('project_id');
                        if ($projectId) {
                            // Limpiar la sesión
                            session()->forget('project_id');
                            return redirect(route('filament.dashboard.resources.projects.edit', $projectId));
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            AttendancesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'view' => Pages\ViewTimesheet::route('/{record}'),
            'edit' => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }
}
