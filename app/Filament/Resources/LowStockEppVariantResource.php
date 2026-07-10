<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LowStockEppVariantResource\Pages;
use App\Models\EppVariant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LowStockEppVariantResource extends Resource
{
    protected static ?string $model = EppVariant::class;

    protected static ?string $modelLabel = 'EPP por Reponer';
    protected static ?string $pluralModelLabel = 'EPPs por Reponer';
    protected static ?string $navigationLabel = 'Reposición de EPPs';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('epp_variants.*')
            ->selectSub(function ($query) {
                $query->selectRaw('COALESCE(SUM(current_stock), 0)')
                    ->from('stocks')
                    ->whereColumn('epp_variants.id', 'stocks.epp_variant_id');
            }, 'current_stock_sum')
            ->whereRaw('(SELECT COALESCE(SUM(current_stock), 0) FROM stocks WHERE stocks.epp_variant_id = epp_variants.id) <= epp_variants.minimum_stock');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('epp_id')
                    ->label('EPP')
                    ->relationship('epp', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->disabled(),
                Forms\Components\TextInput::make('variant_name')
                    ->label('Nombre de Variante')
                    ->disabled(),
                Forms\Components\TextInput::make('minimum_stock')
                    ->label('Stock Mínimo')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('maximum_stock')
                    ->label('Stock Máximo')
                    ->numeric()
                    ->rules(['gte:minimum_stock'])
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
                Tables\Columns\TextColumn::make('current_stock_sum')
                    ->label('Stock Actual')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn(EppVariant $record) => $record->current_stock_sum ?? $record->current_stock),
                Tables\Columns\TextColumn::make('minimum_stock')
                    ->label('Stock Mínimo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maximum_stock')
                    ->label('Stock Máximo')
                    ->sortable(),
            ])
            ->recordUrl(fn(EppVariant $record): string => EppResource::getUrl('edit', ['record' => $record->epp_id]))
            ->filters([
                //
            ])
            ->actions([

            ])
            ->bulkActions([
                // No bulk actions needed for low stock report
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
            'index' => Pages\ListLowStockEppVariants::route('/'),
        ];
    }
}
