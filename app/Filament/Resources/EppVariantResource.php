<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EppVariantResource\Pages;
use App\Filament\Resources\EppVariantResource\RelationManagers;
use App\Models\EppVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EppVariantResource extends Resource
{
    protected static ?string $model = EppVariant::class;

    protected static ?string $modelLabel = 'Variante de EPP';
    protected static ?string $pluralModelLabel = 'Variantes de EPP';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationIcon = 'heroicon-o-variable';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('epp_id')
                    ->label('EPP')
                    ->relationship('epp', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->required()
                    ->maxLength(100)
                    ->unique(table: 'epp_variants', column: 'sku')
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generateSku')
                            ->icon('heroicon-m-sparkles')
                            ->action(function (Forms\Set $set, Forms\Get $get) {
                                $tempVariant = new \App\Models\EppVariant([
                                    'epp_id' => $get('epp_id'),
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('epp.name')
                    ->label('EPP')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                Tables\Columns\TextColumn::make('variant_name')
                    ->label('Nombre / Descripción')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(
                        fn(\App\Models\EppVariant $record, \App\Services\InventoryService $inventoryService) =>
                        $inventoryService->isBelowMinimum($record) ? 'danger' : 'success'
                    ),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Mínimo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maximum_stock')
                    ->label('Máximo')
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
            'index' => Pages\ListEppVariants::route('/'),
            'create' => Pages\CreateEppVariant::route('/create'),
            'edit' => Pages\EditEppVariant::route('/{record}/edit'),
        ];
    }
}
