<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockResource\Pages;
use App\Filament\Resources\StockResource\RelationManagers;
use App\Models\Stock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StockResource extends Resource
{
    protected static ?string $model = Stock::class;

    protected static ?string $modelLabel = 'Stock / Inventario';
    protected static ?string $pluralModelLabel = 'Stocks / Inventarios';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Almacén')
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->live()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Almacén')
                            ->required()
                            ->maxLength(100),
                        Forms\Components\TextInput::make('location')
                            ->label('Ubicación / Dirección')
                            ->maxLength(150),
                    ]),
                Forms\Components\Select::make('warehouse_location_id')
                    ->label('Ubicación de Almacén')
                    ->relationship('warehouseLocation', 'code', modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                        $warehouseId = $get('warehouse_id');
                        if ($warehouseId) {
                            $query->where('warehouse_id', $warehouseId);
                        }
                    })
                    ->required()
                    ->preload()
                    ->searchable()
                    ->disabled(fn (Forms\Get $get): bool => ! $get('warehouse_id'))
                    ->createOptionForm(function (Forms\Get $get) {
                        return [
                            Forms\Components\Select::make('warehouse_id')
                                ->label('Almacén')
                                ->relationship('warehouse', 'name')
                                ->default($get('warehouse_id'))
                                ->required()
                                ->preload()
                                ->searchable(),
                            Forms\Components\TextInput::make('area')
                                ->label('Área')
                                ->maxLength(100),
                            Forms\Components\TextInput::make('rack')
                                ->label('Rack')
                                ->maxLength(50),
                            Forms\Components\TextInput::make('shelf')
                                ->label('Estante')
                                ->maxLength(50),
                            Forms\Components\TextInput::make('section')
                                ->label('Sección')
                                ->maxLength(50),
                            Forms\Components\TextInput::make('bin')
                                ->label('Cajón / Gaveta')
                                ->maxLength(50),
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->columnSpanFull(),
                        ];
                    }),
                Forms\Components\Select::make('epp_variant_id')
                    ->label('Variante (SKU)')
                    ->relationship('eppVariant', 'sku')
                    ->required()
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('current_stock')
                    ->label('Stock Actual')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('minimum_stock')
                    ->label('Stock Mínimo')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('maximum_stock')
                    ->label('Stock Máximo')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouseLocation.code')
                    ->label('Ubicación')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('eppVariant.sku')
                    ->label('SKU / Variante')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock Actual')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maximum_stock')
                    ->label('Stock Máximo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')
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
            'index' => Pages\ListStocks::route('/'),
            'create' => Pages\CreateStock::route('/create'),
            'edit' => Pages\EditStock::route('/{record}/edit'),
        ];
    }
}
