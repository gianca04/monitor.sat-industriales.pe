<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\RelationManagers\VisitsRelationManager;
use App\Filament\Resources\RequestResource\Pages;
use App\Filament\Resources\RequestResource\RelationManagers;
use App\Forms\Components\ClientMainInfo;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Request;
use App\Models\SubClient;
use Filament\Support\Enums\ActionSize;
use App\Models\Visit;
use Filament\Actions\ActionGroup;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Laravel\SerializableClosure\Serializers\Native;

class RequestResource extends Resource
{
    use Translatable;
    protected static ?string $modelLabel = 'Solicitud de Trabajo';
    protected static ?string $model = Request::class;
    protected static ?string $title = 'Solicitud de trabajo';
    protected static ?string $pluralModelLabel = 'Solicitudes de Trabajo';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Gestión';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Section::make('Datos Generales')->schema([
                        Grid::make(1)
                            ->schema([
                                Textarea::make('description')
                                    ->label('Descripción de la Solicitud')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('client_id')
                                    ->prefixIcon('heroicon-m-briefcase')
                                    ->label('Cliente')
                                    ->default(null)
                                    ->native(false)
                                    ->searchable()
                                    ->reactive()
                                    ->helperText('Selecciona el cliente para filtrar las tiendas asociadas')
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
                                                    return [$client->id => $client->business_name];
                                                })
                                                ->toArray();
                                        }
                                    )
                                    ->afterStateUpdated(fn($state, callable $set) => $set('sub_client_id', null))
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
                                    ->createOptionAction(function (FormAction $action) {
                                        return $action
                                            ->modalHeading('Crear nuevo cliente')
                                            ->modalButton('Crear cliente')
                                            ->modalWidth('3x2');
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
                                Forms\Components\Select::make('sub_client_id')
                                    ->required()
                                    ->prefixIcon('heroicon-m-home-modern')
                                    ->label('Tienda') // Título para el campo 'Sede'
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
                                    ->helperText('Selecciona la tienda para esta solicitud.') // Ayuda para el campo 'Sede'

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
                        Grid::make(2)
                            ->schema([
                                Select::make('cotizador_id')
                                    ->required()
                                    ->reactive()
                                    ->prefixIcon('heroicon-m-user')
                                    ->label('Cotizador')
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
                                    ->helperText('Selecciona el supervisor responsable de esta solicitud.')

                                    // Botón para ver información del supervisor
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_supervisor')
                                            ->icon('heroicon-o-eye')
                                            ->tooltip('Ver información del supervisor')
                                            ->color('info')
                                            ->action(function (callable $get) {
                                                $supervisorId = $get('cotizador_id');
                                                if (!$supervisorId) {
                                                    Notification::make()
                                                        ->title('Selecciona un supervisor primero')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            })
                                            ->modalContent(function (callable $get) {
                                                $supervisorId = $get('cotizador_id');
                                                if (!$supervisorId) return null;

                                                $employee = Employee::with('user')->find($supervisorId);
                                                if (!$employee) return null;

                                                return view('filament.components.employee-info-modal', compact('employee'));
                                            })
                                            ->modalHeading('Información del Supervisor')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('2xl')
                                            ->visible(fn(callable $get) => !empty($get('cotizador_id')))
                                    )
                                    ->afterStateHydrated(function (callable $get, callable $set) {
                                        $cotizadorId = $get('cotizador_id');
                                        if ($cotizadorId) {
                                            $employee = Employee::with('user')->find($cotizadorId);
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

                                Select::make('supervisor_id')
                                    ->required()
                                    ->reactive()
                                    ->prefixIcon('heroicon-m-user')
                                    ->label('Supervisor')
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
                                    ->helperText('Selecciona el supervisor responsable de esta solicitud.')

                                    // Botón para ver información del supervisor
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_supervisor')
                                            ->icon('heroicon-o-eye')
                                            ->tooltip('Ver información del supervisor')
                                            ->color('info')
                                            ->action(function (callable $get) {
                                                $supervisorId = $get('supervisor_id');
                                                if (!$supervisorId) {
                                                    Notification::make()
                                                        ->title('Selecciona un supervisor primero')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            })
                                            ->modalContent(function (callable $get) {
                                                $supervisorId = $get('supervisor_id');
                                                if (!$supervisorId) return null;

                                                $employee = Employee::with('user')->find($supervisorId);
                                                if (!$employee) return null;

                                                return view('filament.components.employee-info-modal', compact('employee'));
                                            })
                                            ->modalHeading('Información del Supervisor')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('2xl')
                                            ->visible(fn(callable $get) => !empty($get('supervisor_id')))
                                    )
                                    ->afterStateHydrated(function (callable $get, callable $set) {
                                        $supervisorId = $get('supervisor_id');
                                        if ($supervisorId) {
                                            $employee = Employee::with('user')->find($supervisorId);
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

                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('visit_date')
                                    ->label('Fecha de Visita')
                                    ->required()
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                TimePicker::make('check_in_time')
                                    ->label('Hora de Ingreso')
                                    ->required()
                                    ->seconds(false),
                                TimePicker::make('check_out_time')
                                    ->label('Hora de Salida')
                                    ->required()
                                    ->seconds(false),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Textarea::make('comments')
                                    ->label('Comentarios')
                                    ->maxLength(255)
                                    ->default(null),
                            ]),
                    ]),

                    Section::make('Información Adicional')
                        ->schema([
                            TextInput::make('reference')
                                ->label('Correlativo')
                                ->disabled()->dehydrated()
                                ->visible(fn($operation) => $operation !== 'create')
                                ->maxLength(255),
                            TextInput::make('request_number')
                                ->label('ST')
                                ->columnSpan(1)
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),
                            DatePicker::make('submission_date')
                                ->label('Fecha de Envío de PPTO')
                                ->displayFormat('d/m/Y')
                                ->native(false),
                            TextInput::make('budget')
                                ->label('Presupuesto')
                                ->numeric()
                                ->prefix('S/ ')
                                ->minValue(0)
                                ->step(0.01),
                            Select::make('status')
                                ->label('Estado')
                                ->default(null)
                                ->native(false)
                                ->options([
                                    'pending' => 'Pendiente',
                                    'attended' => 'Atendido',
                                    'rejected' => 'Rechazado',
                                ]),
                        ])->grow(false),
                ])->from('lg')->columnSpan(['md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->label('Correlativo')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn($record) => $record->description)
                    ->label('Descripción')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subClient.client.business_name')
                    ->searchable()
                    ->label('Tienda')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cotizador.full_name')
                    ->searchable()
                    ->label('Cotizador')
                    ->sortable(),
                Tables\Columns\TextColumn::make('supervisor.full_name')
                    ->searchable()
                    ->label('Supervisor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Fecha de visita')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('check_in_time')
                    ->label('Hora de entrada')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('check_out_time')
                    ->label('Hora de salida')
                    ->dateTime('H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('submission_date')
                    ->label('Envío de PPTO')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('budget')
                    ->numeric()
                    ->sortable()
                    ->label('Presupuesto')
                    ->money('S/.')
                    ->color('success')
                    ->weight('bold'),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->options([
                        'pending' => 'Pendiente',
                        'attended' => 'A',
                        'rejected' => 'Rechazado',
                    ]),
                Tables\Columns\TextColumn::make('comments')
                    ->searchable()
                    ->limit(50)
                    ->wrap()
                    ->tooltip(fn($record) => $record->description)
                    ->label('Comentarios')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('generate_report')
                        ->label('Generar PDF')
                        ->color('danger')
                        ->icon('heroicon-o-document')
                        ->url(fn($action) => route('request.consolidated-report.pdf', $action->getRecord()->id))
                        ->openUrlInNewTab()
                        ->visible(fn($action) => $action->getRecord()->visitPhotos()->count() > 0)
                        ->tooltip('Generar reporte PDF del trabajo realizado'),

                ])
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
            VisitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequests::route('/'),
            'create' => Pages\CreateRequest::route('/create'),
            'view' => Pages\ViewRequest::route('/{record}'),
            'edit' => Pages\EditRequest::route('/{record}/edit'),
        ];
    }
}
