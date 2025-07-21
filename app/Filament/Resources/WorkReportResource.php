<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkReportResource\Pages;
use App\Filament\Resources\WorkReportResource\RelationManagers;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkReportResource extends Resource
{
    protected static ?string $modelLabel = 'Reporte de Trabajo';
    protected static ?string $pluralModelLabel = 'Reportes de Trabajo';

    protected static ?string $model = WorkReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make([
                        Forms\Components\Select::make('employee_id')
                            ->required()
                            ->columns(2)
                            ->prefixIcon('heroicon-m-user')
                            ->label('Supervisor') // Título para el campo 'Empleado'
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
                            ->reactive()

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
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $employeeId = $get('employee_id');
                                if ($employeeId) {
                                    // Cargar empleado con usuario relacionado
                                    $employee = Employee::with('user')->find($employeeId);
                                    if ($employee) {
                                        $set('document_type', $employee->document_type);
                                        $set('document_number', $employee->document_number);
                                        $set('address', $employee->address);
                                        $set('date_contract', $employee->date_contract);
                                        // Setear datos del usuario si existe
                                        $set('user_email', $employee->user?->email);
                                        $set('user_is_active', $employee->user?->is_active ? 'Activo' : 'Inactivo');
                                    } else {
                                        $set('user_email', null);
                                        $set('user_is_active', null);
                                    }
                                }
                            }),
                    ]), // Sección de proyecto
                    Section::make([
                        Forms\Components\Select::make('project_id')
                            ->prefixIcon('heroicon-m-briefcase')
                            ->label('Cliente') // Título para el campo 'Cliente'
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
                            ->helperText('Selecciona el cliente para esta cotización.') // Ayuda para el campo de cliente

                            // Botón para ver información del cliente
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('view_client')
                                    ->icon('heroicon-o-eye')
                                    ->tooltip('Ver información del cliente')
                                    ->color('info')
                                    ->action(function (callable $get) {
                                        $projectId = $get('project_id');
                                        if (!$projectId) {
                                            Notification::make()
                                                ->title('Selecciona un cliente primero')
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
                    ]),





                ])
                ->grow(false),
                Section::make('Información del reporte')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nombre del reporte'),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull()
                            ->label('Descripción del reporte'),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkReports::route('/'),
            'create' => Pages\CreateWorkReport::route('/create'),
            'edit' => Pages\EditWorkReport::route('/{record}/edit'),
        ];
    }
}
