<?php

namespace App\Livewire;

use App\Models\StockMovement;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class EppStockMovementsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(StockMovement::query())
            ->columns([
                TextColumn::make('eppVariant.epp.name')
                    ->label('EPP')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('eppVariant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'input' => 'success',
                        'transfer_in' => 'success',
                        'loss' => 'danger',
                        'adjustment_out' => 'danger',
                        'transfer_out' => 'danger',
                        'output' => 'warning',
                        'dispatch' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'input' => 'Ingreso',
                        'transfer_in' => 'Ingreso por traslado',
                        'loss' => 'Merma / Ajuste',
                        'adjustment_out' => 'Merma / Ajuste',
                        'transfer_out' => 'Salida por traslado',
                        'output' => 'Salida',
                        'dispatch' => 'Despacho',
                        default => ucfirst($state),
                    }),
                TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('warehouseLocation.code')
                    ->label('Ubicación')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.employee')
                    ->label('Registrado por')
                    ->formatStateUsing(fn ($record) => $record->user?->employee ? "{$record->user->employee->first_name} {$record->user->employee->last_name}" : ($record->user?->name ?? 'Sistema'))
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                //
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public function render()
    {
        return view('livewire.epp-stock-movements-table');
    }
}
