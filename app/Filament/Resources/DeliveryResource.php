<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryResource\Pages;
use App\Filament\Resources\DeliveryResource\RelationManagers;
use App\Models\Delivery;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryResource extends Resource
{
    protected static ?string $model = Delivery::class;

    protected static ?string $modelLabel = 'Pedido de Entrega';
    protected static ?string $pluralModelLabel = 'Pedidos de Entrega';
    protected static ?string $navigationLabel = 'Pedidos de Entrega';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static bool $shouldRegisterNavigation = false;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Pedido')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('Supervisor Operativo')
                            ->relationship('employee', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->default(fn() => auth()->user()?->employee_id)
                            ->required(),
                        Forms\Components\Select::make('client_id')
                            ->label('Cliente')
                            ->options(\App\Models\Client::pluck('business_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\Delivery $record) {
                                if ($record && $record->subClient) {
                                    $component->state($record->subClient->client_id);
                                }
                            }),
                        Forms\Components\Select::make('sub_client_id')
                            ->label('Tienda')
                            ->relationship('subClient', 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                $clientId = $get('client_id');
                                if ($clientId) {
                                    $query->where('client_id', $clientId);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Almacén de Despacho')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('delivered_by')
                            ->label('Supervisor SSOMA')
                            ->relationship(
                                name: 'deliverer',
                                titleAttribute: 'first_name',
                                modifyQueryUsing: function ($query, Forms\Get $get) {
                                    $query->whereHas('user', fn($q) => $q->whereHas('roles', fn($r) => $r->where('name', 'SSOMA')));
                                    $currentVal = $get('delivered_by');
                                    if ($currentVal) {
                                        $query->orWhere('id', $currentVal);
                                    }
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable()
                            ->preload()
                            ->helperText('Solo son visibles los colaboradores con rol SSOMA.'),

                        Forms\Components\DateTimePicker::make('delivery_date')
                            ->label('Fecha de Entrega')
                            ->nullable()
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('deadline_date')
                            ->label('Fecha Límite')
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->label('Estado de Pedido')
                            ->options(\App\Enums\DeliveryStatus::class)
                            ->required()
                            ->native(false)
                            ->disabled()
                            ->default(\App\Enums\DeliveryStatus::PENDING),
                        Forms\Components\Textarea::make('observations')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Pedido')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Creado por')
                    ->formatStateUsing(fn($record) => "{$record->employee?->first_name} {$record->employee?->last_name}")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subClient.name')
                    ->label('Tienda')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->label('Fecha de Entrega')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deadline_date')
                    ->label('Fecha Límite')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
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
            RelationManagers\DetailsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveries::route('/'),
            'create' => Pages\CreateDelivery::route('/create'),
            'edit' => Pages\EditDelivery::route('/{record}/edit'),
        ];
    }
}
