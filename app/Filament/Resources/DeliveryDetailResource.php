<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeliveryDetailResource\Pages;
use App\Filament\Resources\DeliveryDetailResource\RelationManagers;
use App\Models\DeliveryDetail;
use App\Filament\Resources\DeliveryResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeliveryDetailResource extends Resource
{
    protected static ?string $model = DeliveryDetail::class;

    protected static ?string $modelLabel = 'Detalle de Pedido';
    protected static ?string $pluralModelLabel = "Requerimientos EPP's";
    protected static ?string $navigationLabel = "Requerimientos EPP's";
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Detalle')
                    ->schema([
                        Forms\Components\Select::make('delivery_id')
                            ->label('Pedido de Entrega')
                            ->relationship('delivery', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => "Pedido #" . $record->id . ($record->employee ? " - " . $record->employee->first_name . " " . $record->employee->last_name : ""))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->default(request()->query('delivery_id'))
                            ->afterStateHydrated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $delivery = \App\Models\Delivery::find($state);
                                    if ($delivery) {
                                        $set('employee_id', $delivery->employee_id);
                                        $set('sub_client_id', $delivery->sub_client_id);
                                    }
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $delivery = \App\Models\Delivery::find($state);
                                    if ($delivery) {
                                        $set('employee_id', $delivery->employee_id);
                                        $set('sub_client_id', $delivery->sub_client_id);
                                    }
                                }
                            }),
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
                            ->label('Cantidad')
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
                            ->preload(),
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options(\App\Enums\DeliveryStatus::class)
                            ->required()
                            ->native(false)
                            ->default(\App\Enums\DeliveryStatus::PENDING),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(true),
                Tables\Columns\TextColumn::make('delivery_id')
                    ->label('ID Pedido')
                    ->toggleable()
                    ->toggledHiddenByDefault(true)
                    ->sortable()
                    ->searchable()
                    ->url(fn($record) => DeliveryResource::getUrl('edit', ['record' => $record->delivery_id])),
                Tables\Columns\TextColumn::make('eppVariant.sku')
                    ->label('Variante (SKU)')
                    ->toggleable()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->toggleable()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('employee')
                    ->label('Destinatario')
                    ->toggleable()
                    ->formatStateUsing(fn($record) => $record->employee ? "{$record->employee->first_name} {$record->employee->last_name}" : '-')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('employee', function (Builder $q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('employee.daily_payment')
                    ->label('Pago Diario')
                    ->boolean()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('delivery.delivery_date')
                    ->label('Fecha de Atendido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subClient.name')
                    ->label('Subcliente')
                    ->toggleable()
                    ->searchable()
                    ->sortable(),
            ])
            ->recordUrl(
                fn(DeliveryDetail $record): string => DeliveryResource::getUrl('edit', ['record' => $record->delivery_id])
            )
            ->filters([
                //
            ])
            ->actions([

                ActionGroup::make([

                    Tables\Actions\EditAction::make()
                        ->url(fn($record) => DeliveryResource::getUrl('edit', ['record' => $record->delivery_id])),
                    Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryDetails::route('/'),
            'create' => Pages\CreateDeliveryDetail::route('/create'),
            'edit' => Pages\EditDeliveryDetail::route('/{record}/edit'),
        ];
    }
}
