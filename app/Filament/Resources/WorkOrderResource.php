<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkOrderResource\Pages;
use App\Filament\Resources\WorkOrderResource\RelationManagers;
use App\Models\Request;
use App\Models\WorkOrder;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Date;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;
    protected static ?string $modelLabel = 'Órden de Trabajo';
    protected static ?string $pluralLabel = 'Órdenes de Trabajo';
    protected static ?string $navigationGroup = 'Gestión';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        // Define esta función anónima ANTES de tu esquema de formulario
        $fillRequestData = function ($state, callable $set) {
            // 1. Si no hay ID, limpiar todos los campos.
            if (blank($state)) {
                $set('request_number', null);
                $set('request.description', null);
                $set('request.subClient.name', null);
                $set('request.budget', null);
                return;
            }

            // 2. Busca el Request CON su relación 'subClient' (para más eficiencia)
            $request = Request::with('subClient')->find($state);

            // 3. Llena los campos si se encuentra el Request
            if ($request) {
                $set('request_number', $request->request_number); // O $request->reference, según cuál sea
                $set('request.description', $request->description);
                $set('request.subClient.name', $request->subClient ? $request->subClient->name : null);
                $set('request.budget', $request->budget);
            } else {
                // Opcional: Limpiar si el ID es inválido
                $set('request_number', null);
                $set('request.description', null);
                $set('request.subClient.name', null);
                $set('request.budget', null);
            }
        };
        return $form
            ->schema([

                Forms\Components\Split::make([
                    Section::make('Detalles de la Órden de Trabajo')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    Textarea::make('request.description')
                                        ->label(label: 'Descripción')
                                        ->readOnly()
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(3),

                                    TextInput::make('ot_number')
                                        ->label('Número de OT')
                                        ->unique(ignoreRecord: true),

                                ]),

                            Grid::make(4)
                                ->schema([
                                    Select::make('task_type')
                                        ->label('Tipo de Tarea')
                                        ->options(WorkOrder::getTasksOptions())
                                        ->native(false),

                                    DatePicker::make('start_date')
                                        ->label('Fecha de Inicio')
                                        ->displayFormat('d/m/Y')
                                        ->native(false),

                                    DatePicker::make('end_date')
                                        ->label('Fecha de Fin')
                                        ->displayFormat('d/m/Y')
                                        ->native(false),

                                    Select::make('fracttal_status')
                                        ->label('Estado en Fracttal')
                                        ->options(WorkOrder::getFracttalStatusOptions())
                                        ->native(false),

                                ]),
                            Grid::make(4)
                                ->schema([
                                    DatePicker::make('revision_ot')
                                        ->label('Fecha de Revisión')
                                        ->displayFormat('d/m/Y')
                                        ->native(false),

                                    DatePicker::make('finalized_ot')
                                        ->label('Fecha de finalización')
                                        ->displayFormat('d/m/Y')
                                        ->native(false),

                                    TextInput::make('purchase_order')
                                        ->label('Orden de Compra')
                                        ->numeric()
                                        ->unique(ignoreRecord: true),

                                    TextInput::make('migo')
                                        ->label('MIGO')
                                        ->numeric()
                                        ->unique(ignoreRecord: true),
                                ]),
                            Grid::make(1)
                                ->schema([
                                    Textarea::make('work_order_comments')
                                        ->label('Comentarios')
                                        ->rows(2)
                                        ->cols(10),

                                ]),
                        ]),
                    Section::make('Request')
                        ->schema([
                            Forms\Components\Select::make('request_id')
                                ->label('Solicitud de trabajo')
                                ->searchable()
                                ->options(function (callable $get) {
                                    // ... (Tu lógica de 'options' se queda igual)
                                    $search = $get('search');
                                    $sessionRequestId = session('request_id');

                                    $query = Request::query()
                                        ->select('requests.id', 'requests.reference', 'requests.description', 'sub_clients.name as sub_client_name')
                                        ->leftJoin('sub_clients', 'requests.sub_client_id', '=', 'sub_clients.id')
                                        ->attended()
                                        ->search($search)
                                        ->limit(30);

                                    if ($sessionRequestId) {
                                        $query->orWhere('requests.id', $sessionRequestId);
                                    }

                                    return $query->get()
                                        ->unique('id')
                                        ->mapWithKeys(function ($request) {
                                            $label = "{$request->reference}";
                                            return [$request->id => $label];
                                        })
                                        ->toArray();
                                })
                                ->reactive()
                                ->afterStateHydrated($fillRequestData)  // 👈 AÑADIDO: Para la carga inicial
                                ->afterStateUpdated($fillRequestData)   // 👈 MODIFICADO: Usa la variable
                                ->default(fn() => session('request_id')),
                            Forms\Components\Select::make('work_order_status')
                                ->label('Estado')
                                ->options(WorkOrder::getStatusOptions())
                                ->required()
                                ->native(false),
                            Forms\Components\TextInput::make('request_number')
                                ->label('ST')
                                ->readOnly()
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('request.subClient.name')
                                ->label('Tienda')
                                ->readOnly()
                                ->disabled()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('request.budget')
                                ->label('Presupuesto')
                                ->prefix('S/')
                                ->readOnly()
                                ->disabled()
                                ->dehydrated(false),
                        ])
                        ->grow(false),
                ])
                    ->from('lg')->columnSpan(['md' => 2]),



            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request.id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ot_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('task_type'),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fracttal_status'),
                Tables\Columns\TextColumn::make('revision_ot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('finalized_ot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('purchase_order')
                    ->searchable(),
                Tables\Columns\TextColumn::make('migo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('work_order_status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListWorkOrders::route('/'),
            'create' => Pages\CreateWorkOrder::route('/create'),
            'view' => Pages\ViewWorkOrder::route('/{record}'),
            'edit' => Pages\EditWorkOrder::route('/{record}/edit'),
        ];
    }
}
