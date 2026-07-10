<?php

namespace App\Livewire;

use App\Models\EppVariant;
use App\Filament\Resources\EppResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Component;

class LowStockEppVariantsTable extends Component implements HasTable
{
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EppVariant::query()
                    ->select('epp_variants.*')
                    ->selectSub(function ($query) {
                        $query->selectRaw('COALESCE(SUM(current_stock), 0)')
                            ->from('stocks')
                            ->whereColumn('epp_variants.id', 'stocks.epp_variant_id');
                    }, 'current_stock_sum')
                    ->whereRaw('(SELECT COALESCE(SUM(current_stock), 0) FROM stocks WHERE stocks.epp_variant_id = epp_variants.id) <= epp_variants.minimum_stock')
            )
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
            ->actions([
                Tables\Actions\Action::make('editEpp')
                    ->label('Editar EPP')
                    ->icon('heroicon-o-pencil')
                    ->url(fn(EppVariant $record): string => EppResource::getUrl('edit', ['record' => $record->epp_id])),
            ])
            ->filters([])
            ->bulkActions([]);
    }

    public function render()
    {
        return view('livewire.low-stock-epp-variants-table');
    }
}
