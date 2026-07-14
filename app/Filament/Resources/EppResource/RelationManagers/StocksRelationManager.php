<?php

namespace App\Filament\Resources\EppResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $modelLabel = 'Stock';
    protected static ?string $pluralModelLabel = 'Stock';
    protected static ?string $title = 'Control de Stock';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('epp_variant_id')
                    ->label('Variante (SKU)')
                    ->options(function (RelationManager $livewire) {
                        return \App\Models\EppVariant::where('epp_id', $livewire->getOwnerRecord()->id)
                            ->pluck('sku', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->createOptionForm(function (RelationManager $livewire) {
                        return [
                            Forms\Components\TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->maxLength(100)
                                ->unique(table: 'epp_variants', column: 'sku')
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('generateSku')
                                        ->icon('heroicon-m-sparkles')
                                        ->action(function (Forms\Set $set, Forms\Get $get, RelationManager $livewire) {
                                            $tempVariant = new \App\Models\EppVariant([
                                                'epp_id' => $livewire->getOwnerRecord()->id,
                                                'variant_name' => $get('variant_name')
                                            ]);
                                            $set('sku', $tempVariant->generateSku());
                                        })
                                ),
                            Forms\Components\TextInput::make('variant_name')
                                ->label('Nombre de Variante / Descripción')
                                ->maxLength(150)
                                ->placeholder('Ej: Talla M, Caja x 100, Estándar')
                                ->live(onBlur: true),
                            Forms\Components\TextInput::make('minimum_stock')
                                ->label('Stock Mínimo')
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->required(),
                            Forms\Components\TextInput::make('maximum_stock')
                                ->label('Stock Máximo')
                                ->numeric()
                                ->minValue(0)
                                ->rules([
                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $minStock = $get('minimum_stock');
                                        if ($minStock !== null && $value !== '' && (float) $value < (float) $minStock) {
                                            $fail("El stock máximo debe ser mayor o igual al stock mínimo ({$minStock}).");
                                        }
                                    },
                                ])
                                ->default(0)
                                ->required(),
                            Forms\Components\Toggle::make('active')
                                ->label('Activo')
                                ->default(true)
                                ->required(),
                        ];
                    })
                    ->createOptionUsing(function (array $data, RelationManager $livewire): int {
                        $data['epp_id'] = $livewire->getOwnerRecord()->id;
                        return \App\Models\EppVariant::create($data)->id;
                    }),
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
                    ->disabled(fn(Forms\Get $get): bool => !$get('warehouse_id'))
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
                Forms\Components\TextInput::make('current_stock')
                    ->label('Stock Actual')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('eppVariant.sku')
            ->columns([
                Tables\Columns\TextColumn::make('eppVariant.sku')
                    ->label('Variante (SKU)')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouseLocation.code')
                    ->label('Ubicación')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock Actual')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
             ->headerActions([
                 Tables\Actions\CreateAction::make()
                     ->before(function (Tables\Actions\CreateAction $action, array $data) {
                         $inventoryService = app(\App\Services\InventoryService::class);
                         $exists = $inventoryService->getStock($data['epp_variant_id'], $data['warehouse_location_id']);
                         if ($exists) {
                             \Filament\Notifications\Notification::make()
                                 ->title('Error de validación')
                                 ->body('Ya existe un registro de stock para esta variante en la ubicación seleccionada.')
                                 ->danger()
                                 ->send();
                             $action->halt();
                         }
                     })
                     ->using(function (array $data): \App\Models\Stock {
                         return \App\Models\Stock::create($data);
                     }),
             ])
             ->actions([
                 Tables\Actions\ActionGroup::make([
                     Tables\Actions\ViewAction::make(),
                     Tables\Actions\EditAction::make()
                         ->before(function (Tables\Actions\EditAction $action, array $data, \App\Models\Stock $record) {
                             $inventoryService = app(\App\Services\InventoryService::class);
                             $existingStock = $inventoryService->getStock($data['epp_variant_id'], $data['warehouse_location_id']);
                             if ($existingStock && $existingStock->id !== $record->id) {
                                 \Filament\Notifications\Notification::make()
                                     ->title('Error de validación')
                                     ->body('Ya existe otro registro de stock para esta variante en la ubicación seleccionada.')
                                     ->danger()
                                     ->send();
                                 $action->halt();
                             }
                         }),
                     Tables\Actions\DeleteAction::make(),
                 ])
             ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
