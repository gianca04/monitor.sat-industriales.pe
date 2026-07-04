<?php

namespace App\Filament\Resources\EppResource\RelationManagers;

use App\Actions\AdjustStockAction;
use App\Actions\BulkStockEntryAction;
use App\Actions\TransferStockAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $modelLabel = 'Movimiento';
    protected static ?string $pluralModelLabel = 'Movimientos de Stock';
    protected static ?string $title = 'Movimientos de Stock';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Movimientos son inmutables
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('eppVariant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'input' => 'success',
                        'transfer_in' => 'success',
                        'loss' => 'danger',
                        'adjustment_out' => 'danger',
                        'transfer_out' => 'danger',
                        'output' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'input' => 'Ingreso',
                        'transfer_in' => 'Ingreso por traslado',
                        'loss' => 'Merma / Ajuste',
                        'adjustment_out' => 'Merma / Ajuste',
                        'transfer_out' => 'Salida por traslado',
                        'output' => 'Salida',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('warehouseLocation.code')
                    ->label('Ubicación')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('ingreso')
                    ->label('Ingreso de Stock')
                    ->color('success')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\Select::make('epp_variant_id')
                            ->label('Variante (SKU)')
                            ->options(function (RelationManager $livewire) {
                                return \App\Models\EppVariant::where('epp_id', $livewire->getOwnerRecord()->id)
                                    ->pluck('sku', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Almacén')
                            ->options(\App\Models\Warehouse::all()->pluck('name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live(),
                        Forms\Components\Select::make('warehouse_location_id')
                            ->label('Ubicación de Almacén')
                            ->options(function (Forms\Get $get) {
                                $warehouseId = $get('warehouse_id');
                                if (!$warehouseId)
                                    return [];
                                return \App\Models\WarehouseLocation::where('warehouse_id', $warehouseId)
                                    ->pluck('code', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(Forms\Get $get): bool => !$get('warehouse_id')),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $action = app(\App\Actions\BulkStockEntryAction::class);
                        try {
                            $action->execute([
                                [
                                    'epp_variant_id' => $data['epp_variant_id'],
                                    'warehouse_location_id' => $data['warehouse_location_id'],
                                    'quantity' => $data['quantity'],
                                    'description' => $data['description'],
                                ]
                            ]);
                            Notification::make()
                                ->title('Ingreso registrado con éxito')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al registrar ingreso')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('merma')
                    ->label('Registrar Merma')
                    ->color('danger')
                    ->icon('heroicon-o-minus-circle')
                    ->form([
                        Forms\Components\Select::make('epp_variant_id')
                            ->label('Variante (SKU)')
                            ->options(function (RelationManager $livewire) {
                                return \App\Models\EppVariant::where('epp_id', $livewire->getOwnerRecord()->id)
                                    ->pluck('sku', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Almacén (Origen)')
                            ->options(function (Forms\Get $get) {
                                if (!$get('epp_variant_id'))
                                    return [];
                                return \App\Models\Stock::with('warehouse')
                                    ->where('epp_variant_id', $get('epp_variant_id'))
                                    ->where('current_stock', '>', 0)
                                    ->get()
                                    ->pluck('warehouse.name', 'warehouse_id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('warehouse_location_id')
                            ->label('Ubicación de Almacén (Origen)')
                            ->options(function (Forms\Get $get) {
                                if (!$get('epp_variant_id') || !$get('warehouse_id'))
                                    return [];
                                return \App\Models\Stock::with('warehouseLocation')
                                    ->where('epp_variant_id', $get('epp_variant_id'))
                                    ->where('warehouse_id', $get('warehouse_id'))
                                    ->where('current_stock', '>', 0)
                                    ->get()
                                    ->pluck('warehouseLocation.code', 'warehouse_location_id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(Forms\Get $get): bool => !$get('warehouse_id')),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción (Motivo de merma)')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $action = app(\App\Actions\AdjustStockAction::class);
                        try {
                            $action->execute(
                                $data['epp_variant_id'],
                                $data['warehouse_location_id'],
                                $data['quantity'],
                                'loss',
                                $data['description']
                            );
                            Notification::make()
                                ->title('Merma registrada con éxito')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al registrar merma')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('traslado')
                    ->label('Trasladar Stock')
                    ->color('warning')
                    ->icon('heroicon-o-arrows-right-left')
                    ->form([
                        Forms\Components\Select::make('epp_variant_id')
                            ->label('Variante (SKU)')
                            ->options(function (RelationManager $livewire) {
                                return \App\Models\EppVariant::where('epp_id', $livewire->getOwnerRecord()->id)
                                    ->pluck('sku', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('source_warehouse_id')
                            ->label('Almacén (Origen)')
                            ->options(function (Forms\Get $get) {
                                if (!$get('epp_variant_id'))
                                    return [];
                                return \App\Models\Stock::with('warehouse')
                                    ->where('epp_variant_id', $get('epp_variant_id'))
                                    ->where('current_stock', '>', 0)
                                    ->get()
                                    ->pluck('warehouse.name', 'warehouse_id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('source_location_id')
                            ->label('Ubicación de Origen')
                            ->options(function (Forms\Get $get) {
                                if (!$get('epp_variant_id') || !$get('source_warehouse_id'))
                                    return [];
                                return \App\Models\Stock::with('warehouseLocation')
                                    ->where('epp_variant_id', $get('epp_variant_id'))
                                    ->where('warehouse_id', $get('source_warehouse_id'))
                                    ->where('current_stock', '>', 0)
                                    ->get()
                                    ->pluck('warehouseLocation.code', 'warehouse_location_id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(Forms\Get $get): bool => !$get('source_warehouse_id')),
                        Forms\Components\Select::make('target_warehouse_id')
                            ->label('Almacén (Destino)')
                            ->options(\App\Models\Warehouse::all()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('target_location_id')
                            ->label('Ubicación de Destino')
                            ->options(function (Forms\Get $get) {
                                $warehouseId = $get('target_warehouse_id');
                                if (!$warehouseId)
                                    return [];
                                return \App\Models\WarehouseLocation::where('warehouse_id', $warehouseId)
                                    ->pluck('code', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn(Forms\Get $get): bool => !$get('target_warehouse_id'))
                            ->different('source_location_id'),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción'),
                    ])
                    ->action(function (array $data) {
                        $action = app(\App\Actions\TransferStockAction::class);
                        try {
                            $action->execute(
                                $data['epp_variant_id'],
                                $data['source_location_id'],
                                $data['target_location_id'],
                                $data['quantity'],
                                $data['description']
                            );
                            Notification::make()
                                ->title('Traslado registrado con éxito')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Error al registrar traslado')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                // Movimientos inmutables
            ])
            ->bulkActions([
                // Movimientos inmutables
            ]);
    }
}
