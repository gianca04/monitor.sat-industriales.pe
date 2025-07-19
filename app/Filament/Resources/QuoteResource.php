<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Forms\Components\ubicacion;
use App\Models\Client;
use Filament\Support\View\Components\Modal;
use App\Models\Employee;
use App\Models\Quote;
use App\Models\SubClient;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Actions;

class QuoteResource extends Resource
{

    use Translatable;

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static ?string $modelLabel = 'Cotización';
    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Control de operaciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Forms\Components\Section::make('Acciones Rápidas de Estado')
                        ->description('Cambia el estado de la cotización con un solo clic')
                        ->schema([
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('set_in_progress')
                                    ->label('En Proceso')
                                    ->icon('heroicon-o-clock')
                                    ->color('warning')
                                    ->action(function (callable $set, callable $get) {
                                        $set('status', 'in_progress');
                                        $currentComment = $get('comment') ?? '';
                                        $newComment = $currentComment . "\n[" . now()->format('d/m/Y H:i') . "] Estado cambiado a: En Proceso";
                                        $set('comment', trim($newComment));
                                    })
                                    ->visible(fn(callable $get) => $get('status') !== 'in_progress'),

                                Forms\Components\Actions\Action::make('set_under_review')
                                    ->label('En Revisión')
                                    ->icon('heroicon-o-eye')
                                    ->color('info')
                                    ->action(function (callable $set, callable $get) {
                                        $set('status', 'under_review');
                                        $currentComment = $get('comment') ?? '';
                                        $newComment = $currentComment . "\n[" . now()->format('d/m/Y H:i') . "] Estado cambiado a: En Revisión";
                                        $set('comment', trim($newComment));
                                    })
                                    ->visible(fn(callable $get) => $get('status') !== 'under_review'),

                                Forms\Components\Actions\Action::make('set_sent')
                                    ->label('Enviada')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->color('primary')
                                    ->action(function (callable $set, callable $get) {
                                        $set('status', 'sent');
                                        $currentComment = $get('comment') ?? '';
                                        $newComment = $currentComment . "\n[" . now()->format('d/m/Y H:i') . "] Estado cambiado a: Enviada";
                                        $set('comment', trim($newComment));
                                    })
                                    ->visible(fn(callable $get) => $get('status') !== 'sent'),

                                Forms\Components\Actions\Action::make('set_accepted')
                                    ->label('Aceptada')
                                    ->icon('heroicon-o-check-circle')
                                    ->color('success')
                                    ->requiresConfirmation()
                                    ->modalHeading('Confirmar aceptación')
                                    ->modalDescription('¿Estás seguro de que quieres marcar esta cotización como aceptada?')
                                    ->modalSubmitActionLabel('Sí, aceptar')
                                    ->action(function (callable $set, callable $get) {
                                        $set('status', 'accepted');
                                        $currentComment = $get('comment') ?? '';
                                        $newComment = $currentComment . "\n[" . now()->format('d/m/Y H:i') . "] ✅ Cotización ACEPTADA";
                                        $set('comment', trim($newComment));
                                    })
                                    ->visible(fn(callable $get) => $get('status') !== 'accepted'),

                                Forms\Components\Actions\Action::make('set_rejected')
                                    ->label('Rechazada')
                                    ->icon('heroicon-o-x-circle')
                                    ->color('danger')
                                    ->requiresConfirmation()
                                    ->modalHeading('Confirmar rechazo')
                                    ->modalDescription('¿Estás seguro de que quieres marcar esta cotización como rechazada?')
                                    ->modalSubmitActionLabel('Sí, rechazar')
                                    ->action(function (callable $set, callable $get) {
                                        $set('status', 'rejected');
                                        $currentComment = $get('comment') ?? '';
                                        $newComment = $currentComment . "\n[" . now()->format('d/m/Y H:i') . "] ❌ Cotización RECHAZADA";
                                        $set('comment', trim($newComment));
                                    })
                                    ->visible(fn(callable $get) => $get('status') !== 'rejected'),
                            ])
                                ->alignCenter()
                                ->columns(3),
                        ])
                        ->collapsible()
                        ->visibleOn(['edit', 'view']) // <-- Solo en editar y ver
                        ->collapsed(false),
                    Section::make([ // Sección de empleado
                        Forms\Components\Select::make('employee_id')
                            ->required()
                            ->columns(2)
                            ->prefixIcon('heroicon-m-user')
                            ->label('Cotizador') // Título para el campo 'Empleado'
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
                                    ->tooltip('Ver información del cotizador')
                                    ->color('info')
                                    ->action(function (callable $get) {
                                        $employeeId = $get('employee_id');
                                        if (!$employeeId) {
                                            Notification::make()
                                                ->title('Selecciona un cotizador primero')
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
                                    ->modalHeading('Información del Cotizador')
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

                        /*Section::make('Información del Empleado') // Título de la sección
                            ->collapsed() // Inicia la sección colapsada
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('document_number')
                                    ->label('Número de Documento') // Título para el campo 'Número de Documento'
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('document_number');
                                    })
                                    ->helperText('El número de documento del empleado.'), // Ayuda para el número de documento


                                // Información del usuario relacionado
                                Forms\Components\TextInput::make('user_email')
                                    ->label('Correo Electrónico del Usuario')
                                    ->disabled()
                                    ->default(fn(callable $get) => $get('user_email'))
                                    ->helperText('Correo electrónico asociado al usuario.'),

                            ]),
                */
                            ]),
                

                ])
                    ->from('md')
                    ->columnSpanFull(),



                Split::make([
                    Section::make([
                        Forms\Components\Select::make('client_id')
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
                                Forms\Components\Section::make('Información principal')
                                    ->description('Datos generales del cliente')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Forms\Components\Select::make('document_type')
                                            ->label('Tipo de documento')
                                            ->options([
                                                'RUC' => 'RUC',
                                                'DNI' => 'DNI',
                                                'FOREIGN_CARD' => 'Carné de Extranjería',
                                                'PASSPORT' => 'Pasaporte',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Selecciona tipo de documento')
                                            ->columnSpan(1)
                                            ->prefixIcon('heroicon-o-identification'),
                                        Forms\Components\TextInput::make('document_number')
                                            ->label('Número de documento')
                                            ->placeholder('Ej: 12345678901')
                                            ->required()
                                            ->maxLength(11)
                                            ->minLength(8)
                                            ->alphaNum()
                                            ->columnSpan(1)
                                            ->prefixIcon('heroicon-o-hashtag'),
                                        Forms\Components\Select::make('person_type')
                                            ->label('Tipo de persona')
                                            ->options([
                                                'Natural Person' => 'Persona Natural',
                                                'Legal Entity' => 'Persona Jurídica',
                                            ])
                                            ->required()
                                            ->searchable()
                                            ->placeholder('Selecciona tipo de persona')
                                            ->columnSpan(1)
                                            ->prefixIcon('heroicon-o-user-group'),
                                        Forms\Components\TextInput::make('business_name')
                                            ->label('Razón social')
                                            ->placeholder('Nombre de la empresa o persona')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpan(2)
                                            ->prefixIcon('heroicon-o-building-office-2'),
                                    ])
                                    ->columns(2),
                                Forms\Components\Section::make('Contacto')
                                    ->icon('heroicon-o-phone')
                                    ->description('Información de contacto y dirección')
                                    ->schema([
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->placeholder('Descripción del cliente')
                                            ->columnSpanFull()
                                            ->rows(2)
                                            ->autosize(),
                                        Forms\Components\TextInput::make('address')
                                            ->label('Dirección')
                                            ->placeholder('Dirección fiscal o comercial')
                                            ->maxLength(255)
                                            ->columnSpan(2)
                                            ->prefixIcon('heroicon-o-map-pin'),
                                        Forms\Components\TextInput::make('contact_phone')
                                            ->label('Teléfono de contacto')
                                            ->placeholder('Ej: +51 999 999 999')
                                            ->tel()
                                            ->maxLength(15)
                                            ->columnSpan(1)
                                            ->prefixIcon('heroicon-o-phone'),
                                        Forms\Components\TextInput::make('contact_email')
                                            ->label('Correo electrónico')
                                            ->placeholder('correo@ejemplo.com')
                                            ->email()
                                            ->maxLength(255)
                                            ->columnSpan(1)
                                            ->prefixIcon('heroicon-o-envelope'),
                                    ])
                                    ->columns(2),
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

                        /*Section::make('Información del Cliente') // Título de la sección
                            ->label('Información del Cliente') // Título de la sección
                            ->collapsed() // Inicia la sección colapsada
                            ->schema([
                                Forms\Components\TextInput::make('business_name')
                                    ->label('Nombre del Negocio') // Título para el campo 'Business Name'
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('business_name');
                                    })
                                    ->helperText('El nombre del negocio/cliente.') // Ayuda para el campo 'Business Name'
                                ,

                                Forms\Components\Select::make('document_type_client')
                                    ->label('Tipo de Documento') // Título para el campo 'Tipo de documento'
                                    ->options([
                                        'RUC' => 'RUC',
                                        'DNI' => 'DNI',
                                        'FOREIGN_CARD' => 'Carné de Extranjería',
                                        'PASSPORT' => 'Pasaporte',
                                    ])
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('document_type');
                                    }),

                                Forms\Components\TextInput::make('document_number_client')
                                    ->label('Número de Documento') // Título para el campo 'Document Number'
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('document_number');
                                    })
                                    ->helperText('El número de documento del cliente.'), // Ayuda para el campo 'Document Number'

                                Forms\Components\TextInput::make('contact_phone')
                                    ->label('Teléfono de Contacto') // Título para el campo 'Teléfono de contacto'
                                    ->placeholder('Ej: +51 999 999 999')
                                    ->tel()
                                    ->maxLength(9)
                                    ->minLength(7)
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-phone'),

                                Forms\Components\TextInput::make('contact_email')
                                    ->label('Correo Electrónico') // Título para el campo 'Correo electrónico'
                                    ->placeholder('correo@ejemplo.com')
                                    ->email()
                                    ->disabled()
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->prefixIcon('heroicon-o-envelope'),
                            ]),
*/
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
                                            return [$subClient->id => $subClient->name . ' - ' . $subClient->location];
                                        })
                                        ->toArray();
                                }
                            )
                            ->reactive()
                            ->searchable()
                            ->disabled(fn($get) => !$get('client_id')) // Deshabilita si no hay cliente seleccionado
                            ->helperText('Selecciona el Sede para esta cotización.') // Ayuda para el campo 'Sede'

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
                                Forms\Components\Hidden::make('client_id')
                                    ->default(fn(callable $get) => $get('client_id')),
                                Forms\Components\Section::make('Información de la Sede')
                                    ->description('Datos de la nueva sede')
                                    ->icon('heroicon-o-building-office')
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre de la sede')
                                            ->placeholder('Ej: Sede Central, Sucursal Norte')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-building-office-2'),
                                        Forms\Components\Textarea::make('description')
                                            ->label('Descripción')
                                            ->placeholder('Descripción de la sede')
                                            ->maxLength(500)
                                            ->rows(2)
                                            ->autosize(),
                                        Forms\Components\TextInput::make('location')
                                            ->label('Ubicación')
                                            ->placeholder('Dirección de la sede')
                                            ->required()
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-map-pin'),
                                    ])
                                    ->columns(1),
                                Forms\Components\Section::make('Coordenadas (Opcional)')
                                    ->description('Ubicación geográfica de la sede')
                                    ->icon('heroicon-o-globe-americas')
                                    ->schema([
                                        Forms\Components\TextInput::make('latitude')
                                            ->label('Latitud')
                                            ->placeholder('Ej: -12.046374')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->prefixIcon('heroicon-o-arrow-long-up'),
                                        Forms\Components\TextInput::make('longitude')
                                            ->label('Longitud')
                                            ->placeholder('Ej: -77.042793')
                                            ->numeric()
                                            ->step(0.000001)
                                            ->prefixIcon('heroicon-o-arrow-long-right'),
                                    ])
                                    ->columns(2),
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
                                        // Actualizar los campos de 'name' y 'location' solo si hay un Sede
                                        $set('name', $subClient->name);
                                        $set('location', $subClient->location);
                                    }
                                } else {
                                    // Limpiar los campos si no hay Sede seleccionado
                                    $set('name', null);
                                    $set('location', null);
                                }
                            }),

                        /*Section::make('Información de sede') // Título de la sección
                            ->collapsed() // Inicia la sección colapsada
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del Sede') // Título para el campo 'Sub-client Name'
                                    ->prefixIcon('heroicon-o-user') // Icono añadido
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('name');
                                    })
                                    ->helperText('El nombre del Sede.') // Ayuda para el campo 'Sub-client Name'
                                ,
                                Forms\Components\TextInput::make('location')
                                    ->label('Ubicación') // Título para el campo 'Location'
                                    ->prefixIcon('heroicon-o-map-pin') // Icono añadido
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        return $get('location');
                                    })
                                    ->helperText('La ubicación del Sede.') // Ayuda para el campo 'Location'
                            ]),
*/
                    ]),

                ])
                    ->from('md')
                    ->columnSpanFull(),

                // Step 3: Quote-specific Information
                Section::make('Información de la Cotización')
                    ->columns(2)
                    ->label('Información de la Cotización')
                    ->schema([
                        Forms\Components\TextInput::make('correlative')
                            ->label('Correlativo')
                            ->reactive()
                            ->disabled() // Solo lectura
                            ->default(function (callable $get) {
                                $subClientId = $get('sub_client_id');
                                $pePt = $get('pe_pt');
                                $month = now()->format('m');
                                $year = now()->format('y');
                                $siglas = '';
                                if ($subClientId) {
                                    $subClient = \App\Models\SubClient::find($subClientId);
                                    if ($subClient) {
                                        $siglas = strtoupper(substr($subClient->name, 0, strpos($subClient->name . ' ', ' ')));
                                        $siglas = substr($siglas, 0, 3);
                                    }
                                }
                                $correlative = 'SAT';
                                if ($siglas) {
                                    $correlative .= '-' . $siglas;
                                }
                                if ($pePt) {
                                    $correlative .= '-' . $pePt;
                                }
                                $correlative .= '-' . $month . $year;
                                // No incluye el ID aquí
                                return $correlative;
                            })
                            ->helperText('Previsualización del correlativo. El valor final se asignará automáticamente al guardar.'),

                        Forms\Components\TextInput::make('contractor')
                            ->required()
                            ->maxLength(255)
                            ->label('Contratista')
                            ->default('SAT Industriales S.A.C.')
                            ->helperText('Ingresa el nombre del contratista.'),

                        Forms\Components\Select::make('pe_pt')
                            ->required()

                            ->label('PE/PT')
                            ->options([
                                'PE' => 'PE',
                                'PT' => 'PT',
                                'PE/PE_PT' => 'PE - PT'
                            ])

                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $subClientId = $get('sub_client_id');
                                $pePt = $get('pe_pt');
                                $month = now()->format('m');
                                $year = now()->format('y');
                                $id = $get('id') ?? ''; // Si es edición, puedes obtener el id, si no, dejar vacío
                                $correlative = '';

                                if ($subClientId) {
                                    $subClient = \App\Models\SubClient::find($subClientId);
                                    if ($subClient) {
                                        // Obtener siglas (primeros 3 caracteres hasta el primer espacio)
                                        $siglas = strtoupper(substr($subClient->name, 0, strpos($subClient->name . ' ', ' ')));
                                        $siglas = substr($siglas, 0, 3);
                                        $correlative = 'SAT-' . $siglas;
                                        if ($pePt) {
                                            $correlative .= '-' . $pePt;
                                        }
                                        $correlative .= '-' . $month . $year;
                                        // El id solo estará disponible en edición, para nuevos puedes dejarlo vacío o calcular el siguiente id
                                        if ($id) {
                                            $correlative .= '-' . $id;
                                        }
                                        $set('correlative', $correlative);
                                    }
                                }
                                // ...ya tienes el set de name/location...
                            })
                            ->reactive()
                            ->searchable()
                            ->helperText('Ingresa los detalles de PE/PT.'),

                        Forms\Components\TextInput::make('project_description')
                            ->required()
                            ->maxLength(255)
                            ->label('Descripción del Proyecto')
                            ->helperText('Ingresa la descripción del proyecto.'),

                        Forms\Components\TextInput::make('location')
                            ->maxLength(255)
                            ->label('Ubicación')
                            ->helperText('Ingresa la ubicación del proyecto.'),

                        Forms\Components\DatePicker::make('delivery_term')
                            ->required()
                            ->label('Plazo de Entrega')
                            ->helperText('Selecciona el plazo de entrega para el proyecto.'),
                    ]),



                Tabs::make('MainTabs')

                    ->tabs([
                        Tabs\Tab::make(label: 'TDR')
                            ->icon('heroicon-m-user')
                            ->columns(2)
                            ->schema([
                                Forms\Components\FileUpload::make('TDR')
                                    ->required()
                                    ->previewable(true)
                                    ->label('TDR') // Etiqueta para el campo 'TDR'
                                    ->preserveFilenames()
                                    ->disk('public')
                                    ->visibility('public')
                                    ->directory('cotizaciones/tdr')
                                    ->openable()
                                    ->acceptedFileTypes([
                                        'application/pdf', // PDF
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                                    ])
                                    ->columnSpanFull()
                                    ->downloadable()
                                    ->helperText('Ingresa el TDR.') // Ayuda para el campo 'TDR',
                            ]),

                        Tabs\Tab::make('COTIZACIÓN')
                            ->icon('heroicon-m-user')
                            ->columns(2)
                            ->schema([
                                Forms\Components\FileUpload::make('quote_file')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $set('status', 'under_review');
                                        }
                                    })
                                    ->disk('public')
                                    ->directory('cotizaciones/quote')
                                    ->previewable(true)
                                    ->preserveFilenames()
                                    ->openable()
                                    ->acceptedFileTypes([
                                        'application/pdf', // PDF
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // DOCX
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // XLSX
                                    ])
                                    ->columnSpanFull()
                                    ->downloadable()
                                    ->label('Archivo de Cotización') // Etiqueta para el campo 'Quote File'
                                    ->helperText('Sube el archivo relacionado con la cotización.') // Ayuda para el campo de archivo,
                            ])
                    ])->columnSpanFull(),
                // Campos adicionales

                Section::make('Cotización Detalles')
                    ->description('Gestiona el estado y comentarios de la cotización')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estado Actual')
                                    ->native(false)
                                    ->searchable()
                                    ->default('unassigned')
                                    ->options([
                                        'unassigned' => 'Sin asignar',
                                        'in_progress' => 'En Proceso',
                                        'under_review' => 'En Revisión',
                                        'sent' => 'Enviada',
                                        'rejected' => 'Rechazada',
                                        'accepted' => 'Aceptada',
                                    ])
                                    ->live()
                                    ->prefixIcon('heroicon-o-flag')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Opcional: agregar timestamp del cambio de estado
                                        $set('status_updated_at', now());
                                    }),

                                Textarea::make('comment')
                                    ->label('Comentario')
                                    ->placeholder('Agrega observaciones sobre la cotización...')
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->autosize()
                                    ->helperText('Agrega un comentario adicional sobre la cotización.'),
                            ]),


                    ]),
            ]);
    }


    // ...existing code...
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('correlative')
                    ->label('Correlativo')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-hashtag')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('client.business_name')
                    ->label('Cliente')
                    ->icon('heroicon-m-briefcase')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),

                Tables\Columns\TextColumn::make('sub_client.name')
                    ->label('Sede')
                    ->icon('heroicon-m-home-modern')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),

                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Cotizador')
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->alignLeft(),

                Tables\Columns\TextColumn::make('project_description')
                    ->label('Proyecto')
                    ->limit(30)
                    ->searchable()
                    ->alignLeft(),

                Tables\Columns\TextColumn::make('location')
                    ->label('Ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->limit(20)
                    ->searchable()
                    ->alignLeft(),

                Tables\Columns\TextColumn::make('pe_pt')
                    ->label('PE/PT')
                    ->badge()
                    ->sortable()
                    ->alignCenter(),


                Tables\Columns\TextColumn::make('delivery_term')
                    ->label('Plazo de Entrega')
                    ->date('d/m/Y')
                    ->icon('heroicon-o-calendar')
                    ->sortable()
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('TDR')
                    ->label('TDR')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn($record) => $record->TDR ? Storage::url($record->TDR) : null, true)
                    ->openUrlInNewTab()
                    ->tooltip('Descargar TDR')
                    ->visible(fn($record) => !empty($record->TDR))
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('quote_file')
                    ->label('Archivo Cotización')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn($record) => $record->quote_file ? Storage::url($record->quote_file) : null, true)
                    ->openUrlInNewTab()
                    ->tooltip('Descargar Cotización')
                    ->visible(fn($record) => !empty($record->quote_file))
                    ->alignCenter(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Estado')
                    ->options([
                        'unassigned' => 'Sin asignar',
                        'in_progress' => 'En Proceso',
                        'under_review' => 'En Revisión',
                        'approved' => 'Aprobada',
                        'denied' => 'Denegada',
                        'sent' => 'Enviada',
                        'rejected' => 'Rechazada',
                        'accepted' => 'Aceptada',
                    ])
                    // Usar select nativo para mejor rendimiento
                    ->sortable()
                    ->searchable()
                    ->alignCenter(),

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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'unassigned' => 'Sin asignar',
                        'in_progress' => 'En Proceso',
                        'under_review' => 'En Revisión',
                        'approved' => 'Aprobada',
                        'denied' => 'Denegada',
                        'sent' => 'Enviada',
                        'rejected' => 'Rechazada',
                        'accepted' => 'Aceptada',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('guardarFlash')
                    ->label('Crear Proyecto')
                    ->icon('heroicon-o-puzzle-piece')
                    ->visible(fn($record) => $record->status === 'accepted')
                    ->action(function ($record) {
                        session()->flash('quote_id', $record->id);
                        Notification::make()
                            ->title('Cotización transferida.')
                            ->success()
                            ->send();
                        return redirect('/dashboard/projects/create'); // Cambia esta URL si tu panel usa otra ruta
                    }),
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    // ...existing code...

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuotes::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}
