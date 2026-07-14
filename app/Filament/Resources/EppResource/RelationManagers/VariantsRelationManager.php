<?php

namespace App\Filament\Resources\EppResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Builder;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $modelLabel = 'Variante';
    protected static ?string $pluralModelLabel = 'Variantes';
    protected static ?string $title = 'Variantes de EPP';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(100)
                    ->unique(table: 'epp_variants', column: 'sku', ignoreRecord: true)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generateSku')
                            ->icon('heroicon-m-sparkles')
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                $tempVariant = new \App\Models\EppVariant([
                                    'epp_id' => $this->getOwnerRecord()->id,
                                    'variant_name' => $get('variant_name')
                                ]);
                                $set('sku', $tempVariant->generateSku());
                            })
                    ),
                Forms\Components\TextInput::make('variant_name')
                    ->label('Nombre / Descripción')
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('variant_name')
            ->columns([
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Nombre / Descripción')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(fn (\App\Models\EppVariant $record, \App\Services\InventoryService $inventoryService) => 
                        $inventoryService->isBelowMinimum($record) ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Mínimo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maximum_stock')
                    ->label('Máximo')
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
