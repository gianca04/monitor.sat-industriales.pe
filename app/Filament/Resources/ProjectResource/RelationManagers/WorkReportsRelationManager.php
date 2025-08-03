<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\WorkReportResource\Pages\CreateWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\EditWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\ListWorkReports;
use App\Filament\Resources\WorkReportResource\Pages\ViewWorkReport;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use App\Filament\Resources\WorkReportResource\RelationManagers\PhotosRelationManager;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Quote;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class WorkReportsRelationManager extends RelationManager
{
    protected static ?string $title = 'Reportes de Trabajo';

    protected static ?string $modelLabel = 'Reporte de Trabajo';
    protected static ?string $pluralModelLabel = 'Reportes de Trabajo';
    protected static string $relationship = 'WorkReports';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('MainTabs')
                    ->tabs([
                        // INICIO DE TAB DE INFORMACIÓN GENERAL
                        Tabs\Tab::make('Información general')
                            ->icon('heroicon-o-information-circle')
                            ->columns(2)
                            ->schema([

                                // INICIO DE SELECT DE EMPLEADO
                                Forms\Components\Select::make('employee_id')
                                    ->default(fn() => Auth::user()?->employee_id)->required()
                                    ->columns(2)
                                    ->reactive()
                                    ->prefixIcon('heroicon-m-user')
                                    ->label('Supervisor / Técnico') // Título para el campo 'Empleado'
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
                                    ->placeholder('Seleccionar un empleado') // Placeholder
                                    ->helperText('Selecciona el empleado responsable de esta cotización.') // Ayuda para el campo de empleado

                                    // Botón para ver información del empleado
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_employee')
                                            ->icon('heroicon-o-eye')
                                            ->tooltip('Ver información del supervisor')
                                            ->color('info')
                                            ->action(function (callable $get) {
                                                $employeeId = $get('employee_id');
                                                if (!$employeeId) {
                                                    Notification::make()
                                                        ->title('Selecciona un supervisor primero')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            })
                                            ->modalContent(function (callable $get) {
                                                $employeeId = $get('employee_id');
                                                if (!$employeeId) return null;

                                                $employee = Employee::with('user')->find($employeeId);
                                                if (!$employee) return null;

                                                return view('filament.components.employee-info-modal', compact('employee'));
                                            })
                                            ->modalHeading('Información del Supervisor')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('2xl')
                                            ->visible(fn(callable $get) => !empty($get('employee_id')))
                                    )
                                    ->afterStateHydrated(function (callable $get, callable $set) {
                                        $employeeId = $get('employee_id');
                                        if ($employeeId) {
                                            $employee = Employee::with('user')->find($employeeId);
                                            if ($employee) {
                                                $set('document_type', $employee->document_type);
                                                $set('document_number', $employee->document_number);
                                                $set('address', $employee->address);
                                                $set('date_contract', $employee->date_contract);
                                                $set('user_email', $employee->user?->email);
                                                $set('user_is_active', $employee->user?->is_active ? 'Activo' : 'Inactivo');
                                            } else {
                                                $set('user_email', null);
                                                $set('user_is_active', null);
                                            }
                                        }
                                    }),

                                // FIN DE SELECT DE EMPLEADO

                                // INICIO DE SELECT DE PROYECTO
                                Forms\Components\Select::make('project_id')
                                    ->prefixIcon('heroicon-m-briefcase')
                                    ->default(fn() => session('project_id'))
                                    ->label('Proyecto') // Título para el campo 'Proyecto'
                                    ->options(
                                        function (callable $get) {
                                            return Project::query()
                                                ->select('id', 'name', 'quote_id')
                                                ->when($get('search'), function ($query, $search) {
                                                    $query->where('name', 'like', "%{$search}%")
                                                        ->orWhere('quote_id', 'like', "%{$search}%");
                                                })
                                                ->get()
                                                ->mapWithKeys(function ($project) {
                                                    return [$project->id => $project->name . ' - ' . $project->quote_id];
                                                })
                                                ->toArray();
                                        }
                                    )
                                    ->searchable() // Activa la búsqueda asincrónica
                                    ->reactive() // Hace el campo reactivo
                                    ->afterStateUpdated(fn($state, callable $set) => $set('sub_client_id', null))
                                    ->helperText('Selecciona un proyecto.') // Ayuda para el campo de cliente

                                    // Botón para ver información del proyecto
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_client')
                                            ->icon('heroicon-o-eye')
                                            ->tooltip('Ver información del proyecto')
                                            ->color('info')
                                            ->action(function (callable $get) {
                                                $projectId = $get('project_id');
                                                if (!$projectId) {
                                                    Notification::make()
                                                        ->title('Selecciona un proyecto primero')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            })
                                            ->modalContent(function (callable $get) {
                                                $projectId = $get('project_id');
                                                if (!$projectId) return null;

                                                $project = Project::with('clients')->find($projectId);
                                                if (!$project) return null;

                                                return view('filament.components.project-info-modal', compact('project'));
                                            })
                                            ->modalHeading('Información del Proyecto')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('2xl')
                                            ->visible(fn(callable $get) => !empty($get('project_id')))
                                    )

                                    ->createOptionForm([
                                        Forms\Components\Section::make('Información básica del proyecto')
                                            ->columns(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre del proyecto')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Select::make('quote_id')
                                                    ->label('Cotización')
                                                    ->required()
                                                    ->searchable()
                                                    ->prefixIcon('heroicon-m-calculator')
                                                    ->options(function (callable $get) {
                                                        $search = $get('search');
                                                        $sessionQuoteId = session('quote_id');
                                                        $query = Quote::query()
                                                            ->select('quotes.id', 'quotes.correlative', 'quotes.project_description', 'sub_clients.name as sub_client_name', 'clients.business_name as client_name')
                                                            ->leftJoin('sub_clients', 'quotes.sub_client_id', '=', 'sub_clients.id')
                                                            ->leftJoin('clients', 'quotes.client_id', '=', 'clients.id')
                                                            ->when($search, function ($query) use ($search) {
                                                                $query->where('quotes.correlative', 'like', "%{$search}%")
                                                                    ->orWhere('quotes.project_description', 'like', "%{$search}%")
                                                                    ->orWhere('sub_clients.name', 'like', "%{$search}%")
                                                                    ->orWhere('clients.business_name', 'like', "%{$search}%");
                                                            })
                                                            ->limit(30);

                                                        // Si hay un quote_id en sesión y no está en los resultados, inclúyelo
                                                        if ($sessionQuoteId) {
                                                            $query->orWhere('quotes.id', $sessionQuoteId);
                                                        }

                                                        return $query->get()
                                                            ->unique('id')
                                                            ->mapWithKeys(function ($quote) {
                                                                $label = "{$quote->correlative} - {$quote->project_description} ({$quote->sub_client_name} / {$quote->client_name})";
                                                                return [$quote->id => $label];
                                                            })
                                                            ->toArray();
                                                    })
                                                    ->default(fn() => session('quote_id'))
                                                    ->required(),


                                                // ...existing code...
                                                Forms\Components\DatePicker::make('start_date')
                                                    ->label('Fecha de inicio')
                                                    ->default(now())
                                                    ->required()
                                                    ->maxDate(fn(callable $get) => $get('end_date')), // Valida contra end_date

                                                Forms\Components\DatePicker::make('end_date')
                                                    ->label('Fecha de finalización')
                                                    ->default(now()->addDays(30))
                                                    ->required()
                                                    ->minDate(fn(callable $get) => $get('start_date')), // Valida contra start_date
                                                // ...existing code...

                                            ]),

                                        // Sección: Coordenadas geográficas
                                        Forms\Components\Section::make('Coordenadas geográficas')
                                            ->columns(1)
                                            ->schema([
                                                \App\Forms\Components\ubicacion::make('location')
                                                    ->label('Ubicación en el mapa'),

                                            ]),

                                    ])
                                    ->createOptionUsing(function (array $data): int {
                                        $project = Project::create($data);
                                        return $project->id;
                                    })
                                    ->afterStateUpdated(function (callable $get, callable $set) {
                                        $projectId = $get('project_id');
                                        if ($projectId) {
                                            // Cargar toda la información del cliente en una sola consulta
                                            $project = Project::find($projectId);
                                            if ($project) {
                                                // Actualizar los campos de 'business_name' y 'document_number' solo si hay un cliente
                                                $set('business_name', $project->business_name);
                                                $set('document_type_client', $project->document_type);
                                                $set('document_number_client', $project->document_number);
                                                $set('contact_phone', $project->contact_phone);
                                                $set('contact_email', $project->contact_email);
                                            }
                                        } else {
                                            // Limpiar los campos si no hay cliente seleccionado
                                            $set('business_name', null);
                                            $set('document_number', null);
                                        }
                                    }),
                                // FIN DE SELECT DE PROYECTO

                                // INICIO DE INPUT DE NOMBRE DEL REPORTE
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nombre del reporte'),
                                // FIN DE INPUT DE NOMBRE DEL REPORTE

                                // INICIO DE INPUT DE FECHA
                                Forms\Components\DatePicker::make('created_at')
                                    ->label('Fecha')
                                    ->native(false) // Desactiva el selector nativo para usar el de Filament
                                    ->default(now())
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->helperText('Selecciona la fecha y hora del trabajo'),
                                // FIN DE INPUT DE FECHA

                                // INICIO DE INPUT DE HORA DE INICIO
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Hora de inicio')
                                    ->default(now()->format('H:i'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat(format: 'H:i')
                                    ->helperText('Selecciona la hora de inicio del trabajo'),
                                // FIN DE INPUT DE HORA DE INICIO

                                // INICIO DE INPUT DE HORA DE FINALIZACIÓN
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Hora de finalización')
                                    ->default(now()->format('H:i'))
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat(format: 'H:i')
                                    ->helperText('Selecciona la hora de finalización del trabajo')
                                    // Usamos afterStateUpdated para validar y limpiar el campo
                                    ->afterStateUpdated(function ($state, $get, $livewire) {
                                        $startTime = $get('start_time');
                                        $endTime = $state;

                                        // Si no hay hora de inicio, no validamos
                                        if (!$startTime || !$endTime) {
                                            return;
                                        }

                                        $startCarbon = \Carbon\Carbon::parse($startTime);
                                        $endCarbon = \Carbon\Carbon::parse($endTime);

                                        if ($endCarbon->lessThan($startCarbon)) {
                                            // Envía una notificación de error
                                            Notification::make()
                                                ->title('Error de validación')
                                                ->body('La hora de finalización no puede ser anterior a la hora de inicio.')
                                                ->danger()
                                                ->duration(5000)
                                                ->send();

                                            // Limpiamos el campo 'end_time'
                                            $livewire->form->fill(['end_time' => null]);
                                        }
                                    }),
                                // FIN DE INPUT DE HORA DE FINALIZACIÓN

                            ]),

                        // FIN DE TAB DE INFORMACIÓN GENERAL

                        // INICIO TAB DESCRIPCIÓN DEL REPORTE
                        Tabs\Tab::make('Descripción')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->columns(2)
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->label('Descripción del reporte')
                                    ->required()
                                    ->helperText('Proporciona una descripción detallada del trabajo realizado.')
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                                Forms\Components\RichEditor::make('suggestions')
                                    ->label('Sugerencias')
                                    ->helperText('Proporciona sugerencias o comentarios adicionales sobre el trabajo realizado.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),
                        // FIN TAB DESCRIPCIÓN DEL REPORTE

                        // INICIO DEL TAB DE HERRAMIENTAS Y MATERIALES
                        Tabs\Tab::make('Herramientas y materiales')
                            ->icon('heroicon-o-wrench')
                            ->columns(2)
                            ->schema([
                                Forms\Components\RichEditor::make('tools')
                                    ->label('Herramientas')
                                    ->helperText('Detalla las herramientas utilizadas durante el trabajo.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                                Forms\Components\RichEditor::make('materials')
                                    ->label('Materiales')
                                    ->helperText('Detalla los materiales utilizados durante el trabajo.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),
                        // FIN DEL TAB DE HERRAMIENTAS Y MATERIALES

                        // INICIO DEL TAB DE LISTA DE PERSONAL
                        Tabs\Tab::make('Personal')
                            ->icon('heroicon-o-user-group')
                            ->columns(2)
                            ->schema([
                                Forms\Components\RichEditor::make('personnel')
                                    ->label('Lista de personal')
                                    ->columnSpanFull()
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'bold',
                                        'h2',
                                        'h3',
                                        'orderedList',
                                        'bulletList',
                                        'redo',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),
                        // FIN DL TAB DE LISTA DE PERSONAL

                        // INICIO DE TAB DE FIRMAS
                        Tabs\Tab::make('Firmas')
                            ->icon('heroicon-o-pencil-square')
                            ->columns(2)
                            ->schema([
                                SignaturePad::make('manager_signature')
                                    ->label('Firma del gerente / subgerente')
                                    ->dotSize(2.0)
                                    ->penColor('#000')  // Color negro en modo claro
                                    ->penColorOnDark('#00f')  // Color azul en modo oscuro para mayor visibilidad
                                    ->lineMinWidth(0.2)
                                    ->lineMaxWidth(2.5)
                                    ->throttle(16)
                                    ->minDistance(5)
                                    ->velocityFilterWeight(0.7)
                                    ->confirmable(),
                                SignaturePad::make('supervisor_signature')
                                    ->label('Firma del Validado por supervisor / técnico')
                                    ->dotSize(2.0)
                                    ->penColor('#000')  // Color negro en modo claro
                                    ->penColorOnDark('#00f')  // Color azul en modo oscuro para mayor visibilidad
                                    ->lineMinWidth(0.2)
                                    ->lineMaxWidth(2.5)
                                    ->throttle(16)
                                    ->minDistance(5)
                                    ->velocityFilterWeight(0.7)
                                    ->confirmable(),
                            ]),
                        // FIN DE TAB DE FIRMAS
                    ])->columnSpanFull()
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            PhotosRelationManager::class,
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Reporte')
                    ->searchable()
                    ->extraAttributes(['class' => 'font-bold'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->weight('bold')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Supervisor')
                    ->formatStateUsing(fn($record) => $record->employee->first_name . ' ' . $record->employee->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Evidencias')
                    ->counts('photos')
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        $state == 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Actualizado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->label('Colaborador')
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => session('filter_project_id'))
                    ->placeholder('Todos los proyectos'),

            ])
            ->headerActions([

                Tables\Actions\Action::make('create_advanced')
                    ->label('Crear reporte')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->tooltip('Ir al formulario completo de tareos con todas las funcionalidades')
                    ->action(function () {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource create
                        return redirect(route('filament.dashboard.resources.work-reports.create'));
                    }),
                Tables\Actions\Action::make('manage_all')
                    ->label('Todos los reportes')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->tooltip('Ver y gestionar todos los reportes del proyecto en la vista completa')
                    ->action(function () {
                        // Guardar el project_id en la sesión para filtros
                        session(['filter_project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource index
                        return redirect(route('filament.dashboard.resources.work-reports.index'));
                    }),
            ])
            ->actions([


                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger'),

                RelationManagerAction::make('photos-relation-manager')
                    ->label('Ver fotografías')
                    ->slideOver(true)
                    ->relationManager(PhotosRelationManager::make()),


                Tables\Actions\Action::make('generate_report')
                    ->label('Generar PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document')
                    ->url(fn($action) => route('work-report.pdf', $action->getRecord()->id))
                    ->openUrlInNewTab()
                    ->visible(fn($action) => $action->getRecord()->photos()->count() > 0)
                    ->tooltip('Generar reporte PDF del trabajo realizado'),

                Tables\Actions\Action::make('generate_word_report')
                    ->label('Generar Word')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn($action) => route('work-report.word', $action->getRecord()->id))
                    ->openUrlInNewTab()
                    ->visible(fn($action) => $action->getRecord()->photos()->count() > 0)
                    ->tooltip('Generar reporte Word del trabajo realizado'),

            ])
            ->emptyStateHeading('No hay reportes registrados')
            ->emptyStateDescription('Comienza creando el primer reporte para este proyecto.')
            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
