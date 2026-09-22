<?php

namespace App\Filament\Resources\RequirementResource\RelationManagers;

use App\Filament\Resources\ItemResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RequirementListsRelationManager extends RelationManager
{
    protected static string $relationship = 'requirementLists';

    protected static ?string $title = 'Ítems Requeridos';

    protected static ?string $modelLabel = 'Ítem';

    protected static ?string $pluralModelLabel = 'Ítems Requeridos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('Ítem')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm(fn (Form $form): Form => ItemResource::form($form))
                    ->createOptionUsing(function (array $data): int {
                        $data['created_by'] = auth()->id();

                        if (empty($data['sku'])) {
                            $subcategoryId = $data['subcategory_id'] ?? 0;
                            $data['sku'] = app(\App\Actions\GenerateItemSkuAction::class)->execute($subcategoryId);
                        }

                        $item = \App\Models\Item::create($data);

                        return $item->id;
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(0.01)
                    ->default(1)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('item.sku')
                    ->label('SKU')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.name')
                    ->label('Ítem')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('item.unit.symbol')
                    ->label('Unidad')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Ítem'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
