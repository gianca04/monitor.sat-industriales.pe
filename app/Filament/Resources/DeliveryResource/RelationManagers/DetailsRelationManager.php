<?php

namespace App\Filament\Resources\DeliveryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $modelLabel = 'Detalle de Pedido';
    protected static ?string $pluralModelLabel = "Requerimientos EPP's";
    protected static ?string $title = 'Detalles del Pedido';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Categoría')
                    ->options(\App\Models\Category::all()->pluck('name', 'id'))
                    ->live()
                    ->searchable()
                    ->native(false)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\DeliveryDetail $record) {
                        if ($record && $record->eppVariant) {
                            $epp = $record->eppVariant->epp;
                            if ($epp) {
                                $firstSub = $epp->subcategories()->first();
                                if ($firstSub) {
                                    $component->state($firstSub->category_id);
                                }
                            }
                        }
                    }),
                Forms\Components\Select::make('subcategory_id')
                    ->label('Subcategoría')
                    ->options(function (Forms\Get $get) {
                        $categoryId = $get('category_id');
                        if ($categoryId) {
                            return \App\Models\Subcategory::where('category_id', $categoryId)->pluck('name', 'id');
                        }
                        return \App\Models\Subcategory::all()->pluck('name', 'id');
                    })
                    ->live()
                    ->searchable()
                    ->native(false)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\DeliveryDetail $record) {
                        if ($record && $record->eppVariant) {
                            $epp = $record->eppVariant->epp;
                            if ($epp) {
                                $firstSub = $epp->subcategories()->first();
                                if ($firstSub) {
                                    $component->state($firstSub->id);
                                }
                            }
                        }
                    }),
                Forms\Components\Select::make('epp_id')
                    ->label('EPP')
                    ->options(function (Forms\Get $get) {
                        $subcategoryId = $get('subcategory_id');
                        if ($subcategoryId) {
                            return \App\Models\Epp::whereHas('subcategories', function ($q) use ($subcategoryId) {
                                $q->where('subcategories.id', $subcategoryId);
                            })->pluck('name', 'id');
                        }
                        return \App\Models\Epp::all()->pluck('name', 'id');
                    })
                    ->live()
                    ->searchable()
                    ->native(false)
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\DeliveryDetail $record) {
                        if ($record && $record->eppVariant) {
                            $component->state($record->eppVariant->epp_id);
                        }
                    }),
                Forms\Components\Select::make('epp_variant_id')
                    ->label('Variante (SKU)')
                    ->options(function (Forms\Get $get) {
                        $eppId = $get('epp_id');
                        if ($eppId) {
                            return \App\Models\EppVariant::where('epp_id', $eppId)->pluck('sku', 'id');
                        }
                        return \App\Models\EppVariant::all()->pluck('sku', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->native(false)
                    ->preload(),
                Forms\Components\Placeholder::make('epp_photos')
                    ->label('Fotos del EPP')
                    ->content(function (Forms\Get $get) {
                        $eppId = $get('epp_id');
                        if (!$eppId) {
                            return 'Seleccione un EPP para ver sus fotos.';
                        }

                        $epp = \App\Models\Epp::find($eppId);
                        if (!$epp || empty($epp->photos)) {
                            return 'El EPP seleccionado no tiene fotos registradas.';
                        }

                        $html = '<div style="display: flex; gap: 12px; overflow-x: auto; padding: 8px 4px; max-width: 100%; scrollbar-width: thin;">';
                        foreach ($epp->photos as $photo) {
                            $url = asset('storage/' . $photo);
                            $html .= '<a href="' . $url . '" target="_blank" style="flex-shrink: 0;"><img src="' . $url . '" style="height: 110px; width: 110px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.05);" /></a>';
                        }
                        $html .= '</div>';

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Solicitado')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                Forms\Components\Select::make('employee_id')
                    ->label('Destinatario (Personal)')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable()
                    ->native(false)
                    ->preload(),
                Forms\Components\Select::make('sub_client_id')
                    ->label('Subcliente')
                    ->relationship('subClient', 'name')
                    ->searchable()
                    ->native(false)
                    ->preload()
                    ->default(fn(RelationManager $livewire) => $livewire->getOwnerRecord()->sub_client_id),
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options(\App\Enums\DeliveryStatus::class)
                    ->required()
                    ->native(false)
                    ->default(\App\Enums\DeliveryStatus::PENDING)
                    ->disabled()
                    ->dehydrated(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('quantity')
            ->columns([
                Tables\Columns\TextColumn::make('eppVariant.sku')
                    ->label('Variante (SKU)')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivered_quantity')
                    ->label('Entregado')
                    ->numeric(),
                Tables\Columns\TextColumn::make('employee')
                    ->label('Destinatario')
                    ->formatStateUsing(fn($record) => $record->employee ? "{$record->employee->first_name} {$record->employee->last_name}" : '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('employee.daily_payment')
                    ->label('Pago Diario')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery.delivery_date')
                    ->label('Fecha de Atendido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('subClient.name')
                    ->label('Tienda')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('despachar')
                    ->label('Despachar')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(fn(\App\Models\DeliveryDetail $record): bool => $record->status !== \App\Enums\DeliveryStatus::DELIVERED)
                    ->mountUsing(function (Forms\ComponentContainer $form, \App\Models\DeliveryDetail $record) {
                        $form->fill([
                            'sku' => $record->eppVariant->sku,
                            'required_quantity' => $record->quantity,
                            'delivered_quantity' => $record->delivered_quantity,
                            'remaining_quantity' => $record->quantity - $record->delivered_quantity,
                        ]);
                    })
                    ->form([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU')
                                    ->disabled(),
                                Forms\Components\TextInput::make('required_quantity')
                                    ->label('Cantidad requerida')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('delivered_quantity')
                                    ->label('Cantidad entregada')
                                    ->numeric()
                                    ->disabled(),
                                Forms\Components\TextInput::make('remaining_quantity')
                                    ->label('Cantidad pendiente')
                                    ->numeric()
                                    ->disabled(),
                            ]),
                        Forms\Components\Repeater::make('dispatches')
                            ->label('Distribución de Despacho')
                            ->schema([
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Almacén')
                                    ->options(\App\Models\Warehouse::all()->pluck('name', 'id'))
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->live(),
                                Forms\Components\Select::make('warehouse_location_id')
                                    ->label('Ubicación')
                                    ->options(function (Forms\Get $get, \App\Models\DeliveryDetail $record) {
                                        $warehouseId = $get('warehouse_id');
                                        if (!$warehouseId)
                                            return [];

                                        return \App\Models\Stock::with('warehouseLocation')
                                            ->where('epp_variant_id', $record->epp_variant_id)
                                            ->where('warehouse_id', $warehouseId)
                                            ->where('current_stock', '>', 0)
                                            ->get()
                                            ->mapWithKeys(fn($stock) => [
                                                $stock->warehouse_location_id => "{$stock->warehouseLocation->code} (Disponible: {$stock->current_stock})"
                                            ]);
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn(Forms\Get $get): bool => !$get('warehouse_id')),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->rules([
                                        fn(Forms\Get $get, \App\Models\DeliveryDetail $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                            $locationId = $get('warehouse_location_id');
                                            if (!$locationId)
                                                return;

                                            $stock = \App\Models\Stock::where('epp_variant_id', $record->epp_variant_id)
                                                ->where('warehouse_location_id', $locationId)
                                                ->first();

                                            $available = $stock ? $stock->current_stock : 0;
                                            if ($value > $available) {
                                                $fail("La cantidad supera el stock disponible en esta ubicación ({$available}).");
                                            }
                                        }
                                    ]),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->required()
                            ->rules([
                                fn(\App\Models\DeliveryDetail $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $totalDispatched = collect($value)->sum('quantity');
                                    $remaining = $record->quantity - $record->delivered_quantity;
                                    if ($totalDispatched > $remaining) {
                                        $fail("La cantidad total a despachar ({$totalDispatched}) supera la cantidad pendiente ({$remaining}).");
                                    }
                                }
                            ])
                    ])
                    ->action(function (array $data, \App\Models\DeliveryDetail $record) {
                        try {
                            app(\App\Actions\DispatchDeliveryDetailAction::class)->execute($record, $data['dispatches']);

                            \Filament\Notifications\Notification::make()
                                ->title('Despacho registrado con éxito')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al despachar')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
