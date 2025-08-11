<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Resources\ProjectResource\RelationManagers\EmployeesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\TimesheetsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\WorkReportsRelationManager;
use App\Forms\Components\ClientMainInfo;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\SubClient;
use App\Models\WorkReport;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Split;
use FontLib\Table\Type\name;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{

    use Translatable;

    protected static ?string $pluralModelLabel = 'Proyectos';
    protected static ?string $modelLabel = 'Proyecto';

    protected static ?string $model = Project::class;

    protected static ?string $navigationGroup = 'Control de operaciones';
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    // BUSQUEDA GLOBAL DE PROYECTOS
    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];  // Verifica que estos atributos sean los más relevantes para la búsqueda
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Nombre' => $record->name,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        // Optimiza la consulta, asegurando que solo cargue lo necesario
        return parent::getGlobalSearchEloquentQuery()
            ; // Selecciona solo las columnas necesarias del modelo Employee
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección: Información básica del proyecto
                Forms\Components\Section::make('Información básica del proyecto')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del proyecto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('quote_id')
                            ->label('Cotización')
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
                            ->default(fn() => session('quote_id')),


                        // ...existing code...
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de inicio')
                            ->default(now())
                            ->required()
                            ->maxDate(fn(callable $get) => $get('end_date')), // Valida contra end_date

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de finalización')
                            //->default(now()->addDays(30))
                            ->required()
                            ->minDate(fn(callable $get) => $get('start_date')), // Valida contra start_date
                        // ...existing code...

                        Forms\Components\Placeholder::make('status_text')
                            ->label('Estado del proyecto:')
                            ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600'])
                            ->content(fn($record) => $record?->status_text ?? 'Sin definir'),
                    ]),

                Split::make([
                    Section::make([
                        Forms\Components\Select::make('client_id')
                            ->required()
                            ->prefixIcon('heroicon-m-briefcase')
                            ->label('Cliente') // Título para el campo 'Cliente'
                            ->options(
                                function (callable $get) {
                                    return Client::query()
                                        ->select('id', 'business_name', 'document_number')
                                        ->when($get('search'), function ($query, $search) {
                                            $query->where('business_name', 'like', "%{$search}%")
                                                ->orWhere('document_number', 'like', "%{$search}%");
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($client) {
                                            return [$client->id => $client->business_name . ' - ' . $client->document_number];
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
                                        $clientId = $get('client_id');
                                        if (!$clientId) {
                                            Notification::make()
                                                ->title('Selecciona un cliente primero')
                                                ->warning()
                                                ->send();
                                            return;
                                        }
                                    })
                                    ->modalContent(function (callable $get) {
                                        $clientId = $get('client_id');
                                        if (!$clientId) return null;

                                        $client = Client::with('subClients')->find($clientId);
                                        if (!$client) return null;

                                        return view('filament.components.client-info-modal', compact('client'));
                                    })
                                    ->modalHeading('Información del Cliente')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Cerrar')
                                    ->modalWidth('2xl')
                                    ->visible(fn(callable $get) => !empty($get('client_id')))
                            )

                            ->createOptionForm([
                                ClientMainInfo::make()

                            ])
                            ->createOptionUsing(function (array $data): int {
                                $client = Client::create($data);
                                return $client->id;
                            })
                            ->createOptionAction(function (FormAction $action) {
                                return $action
                                    ->modalHeading('Crear nuevo cliente')
                                    ->modalButton('Crear cliente')
                                    ->modalWidth('3xl');
                            })

                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $clientId = $get('client_id');
                                if ($clientId) {
                                    // Cargar toda la información del cliente en una sola consulta
                                    $client = Client::find($clientId);
                                    if ($client) {
                                        // Actualizar los campos de 'business_name' y 'document_number' solo si hay un cliente
                                        $set('business_name', $client->business_name);
                                        $set('document_type_client', $client->document_type);
                                        $set('document_number_client', $client->document_number);
                                        $set('contact_phone', $client->contact_phone);
                                        $set('contact_email', $client->contact_email);
                                    }
                                } else {
                                    // Limpiar los campos si no hay cliente seleccionado
                                    $set('business_name', null);
                                    $set('document_number', null);
                                }
                            }),
                    ]),

                    Section::make([
                        Forms\Components\Select::make('sub_client_id')
                            ->required()
                            ->prefixIcon('heroicon-m-home-modern')
                            ->label('Sede') // Título para el campo 'Sede'
                            ->options(
                                function (callable $get) {
                                    $clientId = $get('client_id');
                                    return SubClient::where('client_id', $clientId)
                                        ->get()
                                        ->mapWithKeys(function ($subClient) {
                                            return [$subClient->id => $subClient->name];
                                        })
                                        ->toArray();
                                }
                            )
                            ->reactive()
                            ->searchable()
                            ->disabled(fn($get) => !$get('client_id')) // Deshabilita si no hay cliente seleccionado
                            ->helperText('Selecciona el Sede para esta cotización.') // Ayuda para el campo 'Sede'

                            // Cuando se carga un registro existente, seleccionar automáticamente el cliente
                            ->afterStateHydrated(function ($state, callable $set) {
                                if ($state) {
                                    $subClient = SubClient::find($state);
                                    if ($subClient) {
                                        $set('client_id', $subClient->client_id);
                                    }
                                }
                            })

                            // Botón para ver información de la sede
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('view_sub_client')
                                    ->icon('heroicon-o-eye')
                                    ->tooltip('Ver información de la sede')
                                    ->color('info')
                                    ->action(function (callable $get) {
                                        $subClientId = $get('sub_client_id');
                                        if (!$subClientId) {
                                            Notification::make()
                                                ->title('Selecciona una sede primero')
                                                ->warning()
                                                ->send();
                                            return;
                                        }
                                    })
                                    ->modalContent(function (callable $get) {
                                        $subClientId = $get('sub_client_id');
                                        if (!$subClientId) return null;

                                        $subClient = SubClient::with('client')->find($subClientId);
                                        if (!$subClient) return null;

                                        return view('filament.components.sub-client-info-modal', compact('subClient'));
                                    })
                                    ->modalHeading('Información de la Sede')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Cerrar')
                                    ->modalWidth('2xl')
                                    ->visible(fn(callable $get) => !empty($get('sub_client_id')))
                            )

                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del subcliente')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user'),

                                Forms\Components\TextInput::make('address')
                                    ->label('Dirección')
                                    ->columnSpanFull()
                                    ->placeholder('Dirección del subcliente')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map-pin'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->maxLength(500)
                                    ->autosize()
                                    ->columnSpanFull(),
                            ])
                            ->createOptionUsing(function (array $data, callable $get): int {
                                $data['client_id'] = $get('client_id');
                                $subClient = SubClient::create($data);
                                return $subClient->id;
                            })
                            ->createOptionAction(function (FormAction $action) {
                                return $action
                                    ->modalHeading('Crear nueva sede')
                                    ->modalButton('Crear sede')
                                    ->modalWidth('2xl');
                            })
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $subClientId = $get('sub_client_id');
                                if ($subClientId) {
                                    // Cargar toda la información del Sede en una sola consulta
                                    $subClient = SubClient::find($subClientId);
                                    if ($subClient) {
                                    }
                                } else {
                                    // Limpiar los campos si no hay Sede seleccionado
                                    $set('name', null);
                                    $set('location', null);
                                }
                            }),
                    ]),
                ])
                    ->from('md')
                    ->columnSpanFull(),

                // Sección: Coordenadas geográficas
                Forms\Components\Section::make('Coordenadas geográficas')
                    ->columns(1)
                    ->collapsed()
                    ->description('Ubicación geográfica del proyecto')
                    ->schema([
                        \App\Forms\Components\ubicacion::make('location')
                            ->label('Ubicación en el mapa')
                            ->helperText('Selecciona la ubicación del proyecto en el mapa o ingresa una dirección para buscar.')
                            ->columnSpanFull()
                            ->default([
                                'latitude' => -12.046374,
                                'longitude' => -77.042793,
                                'location' => ''
                            ]),
                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table

            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->alignJustify()
                    ->extraAttributes(['class' => 'font-bold'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('subClient.client.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subClient.name')
                    ->label('Subcliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status_text')
                    ->label('Estado del proyecto')
                    ->formatStateUsing(fn($state, $record) => $record?->status_text ?? 'Sin definir')
                    ->colors([
                        'gray' => fn($state) => $state === 'Sin definir',
                        'primary' => fn($state) => $state === 'No iniciado',
                        'warning' => fn($state) => $state === 'En proceso',
                        'success' => fn($state) => $state === 'Culminado',
                    ]),


                Tables\Columns\TextColumn::make('quote.correlative')
                    ->label('Correlativo de Cotización')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($record) => $record->quote ? "{$record->quote->correlative} - {$record->quote->project_description}" : 'Sin cotización'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_address')
                    ->label('Ubicación')
                    ->formatStateUsing(function ($record) {
                        return $record->location_address ?? 'Sin ubicación';
                    }),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Coordenadas')
                    ->formatStateUsing(function ($record) {
                        return $record->coordinates ?? 'Sin coordenadas';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                Tables\Filters\SelectFilter::make('status_text')
                    ->label('Estado del proyecto')
                    ->native(false)
                    ->options([
                        'No iniciado' => 'No iniciado',
                        'En proceso' => 'En proceso',
                        'Culminado' => 'Culminado',
                        'Sin definir' => 'Sin definir',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return $query->whereRaw("(
                            CASE
                                WHEN start_date IS NOT NULL AND ? < DATE(start_date) THEN 'No iniciado'
                                WHEN end_date IS NOT NULL AND ? > DATE(end_date) THEN 'Culminado'
                                WHEN start_date IS NOT NULL AND end_date IS NOT NULL AND ? >= DATE(start_date) AND ? <= DATE(end_date) THEN 'En proceso'
                                ELSE 'Sin definir'
                            END
                        ) = ?", [now()->toDateString(), now()->toDateString(), now()->toDateString(), now()->toDateString(), $data['value']]);
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
            TimesheetsRelationManager::class,
            WorkReportsRelationManager::class,
            EmployeesRelationManager::class, // Relación con empleados (supervisores)
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
