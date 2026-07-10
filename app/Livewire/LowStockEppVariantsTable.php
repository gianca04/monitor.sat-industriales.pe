<?php

namespace App\Livewire;

use App\Models\EppVariant;
use App\Filament\Resources\EppResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class LowStockEppVariantsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
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
                TextColumn::make('epp.name')
                    ->label('EPP')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('variant_name')
                    ->label('Nombre / Descripción')
                    ->searchable(),
                TextColumn::make('current_stock_sum')
                    ->label('Stock Actual')
                    ->badge()
                    ->color('danger')
                    ->getStateUsing(fn(EppVariant $record) => $record->current_stock_sum ?? $record->current_stock),
                TextColumn::make('minimum_stock')
                    ->label('Stock Mínimo')
                    ->sortable(),
                TextColumn::make('maximum_stock')
                    ->label('Stock Máximo')
                    ->sortable(),
            ])
            ->recordUrl(fn(EppVariant $record): string => EppResource::getUrl('edit', ['record' => $record->epp_id]))
            ->actions([
                Action::make('editEpp')
                    ->label('Gestionar')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('info')
                    ->url(fn(EppVariant $record): string => EppResource::getUrl('edit', ['record' => $record->epp_id])),
            ]);
    }

    public function render()
    {
        return view('livewire.low-stock-epp-variants-table');
    }
}
