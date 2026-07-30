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
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('user.employee')
                    ->label('Registrado por')
                    ->formatStateUsing(fn ($record) => $record->user?->employee ? "{$record->user->employee->first_name} {$record->user->employee->last_name}" : ($record->user?->name ?? 'Sistema'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($state) => $state),
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
                Tables\Actions\ViewAction::make()
                    ->form([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('epp_name')
                                    ->label('EPP')
                                    ->content(fn ($record) => $record?->eppVariant?->epp?->name),
                                \Filament\Forms\Components\Placeholder::make('sku')
                                    ->label('SKU')
                                    ->content(fn ($record) => $record?->eppVariant?->sku),
                                \Filament\Forms\Components\Placeholder::make('type')
                                    ->label('Tipo')
                                    ->content(fn ($record) => match ($record?->type) {
                                        'input' => 'Ingreso',
                                        'transfer_in' => 'Ingreso por traslado',
                                        'loss' => 'Merma / Ajuste',
                                        'adjustment_out' => 'Merma / Ajuste',
                                        'transfer_out' => 'Salida por traslado',
                                        'output' => 'Salida',
                                        'dispatch' => 'Despacho',
                                        default => $record?->type ? ucfirst($record->type) : null,
                                    }),
                                \Filament\Forms\Components\Placeholder::make('quantity')
                                    ->label('Cantidad')
                                    ->content(fn ($record) => $record?->quantity),
                                \Filament\Forms\Components\Placeholder::make('warehouse')
                                    ->label('Almacén')
                                    ->content(fn ($record) => $record?->warehouse?->name),
                                \Filament\Forms\Components\Placeholder::make('location')
                                    ->label('Ubicación')
                                    ->content(fn ($record) => $record?->warehouseLocation?->code),
                                \Filament\Forms\Components\Placeholder::make('user')
                                    ->label('Registrado por')
                                    ->content(fn ($record) => $record?->user?->employee ? "{$record->user->employee->first_name} {$record->user->employee->last_name}" : ($record?->user?->name ?? 'Sistema')),
                                \Filament\Forms\Components\Placeholder::make('created_at')
                                    ->label('Fecha')
                                    ->content(fn ($record) => $record?->created_at?->format('d/m/Y H:i')),
                                \Filament\Forms\Components\Placeholder::make('description')
                                    ->label('Descripción')
                                    ->content(fn ($record) => $record?->description)
                                    ->columnSpanFull(),
                            ])
                    ])
            ])
            ->recordAction(Tables\Actions\ViewAction::class)
            ->bulkActions([
                //
            ]);
    }

    public function render()
    {
        return view('livewire.epp-stock-movements-table');
    }
}
