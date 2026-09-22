<?php

namespace App\Filament\Resources;

use App\Actions\GenerateItemSkuAction;
use App\Filament\Resources\ItemResource\Pages;
use App\Models\Item;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $modelLabel = 'Ítem';

    protected static ?string $pluralModelLabel = 'Ítems';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sku')
                    ->label('SKU')
                    ->placeholder('Auto-generado si se deja en blanco')
                    ->maxLength(255)
                    ->suffixAction(
                        Forms\Components\Actions\Action::make('generateSku')
                            ->icon('heroicon-m-sparkles')
                            ->tooltip('Generar SKU automático')
                            ->action(function ($set, $get) {
                                $subcategoryId = $get('subcategory_id');
                                $sku = app(GenerateItemSkuAction::class)->execute($subcategoryId ?: 0);
                                $set('sku', $sku);
                            })
                    ),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Ítem')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('subcategory_id')
                    ->label('Subcategoría')
                    ->relationship('subcategory', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre de la Subcategoría')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->afterStateUpdated(function ($state, $set, $get) {
                        if (empty($get('sku')) && $state) {
                            $sku = app(GenerateItemSkuAction::class)->execute($state);
                            $set('sku', $sku);
                        }
                    }),
                Forms\Components\Select::make('unit_id')
                    ->label('Unidad de Medida')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('ej: Kilogramo, Unidad, Metro')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('symbol')
                            ->label('Símbolo')
                            ->placeholder('ej: kg, und, m')
                            ->required()
                            ->maxLength(255),
                    ]),
                Forms\Components\FileUpload::make('photo')
                    ->label('Foto del Ítem')
                    ->image()
                    ->directory('items')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subcategory.name')
                    ->label('Subcategoría')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('unit.symbol')
                    ->label('Unidad')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subcategory_id')
                    ->label('Subcategoría')
                    ->relationship('subcategory', 'name'),
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unidad')
                    ->relationship('unit', 'name'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListItems::route('/'),
            // 'create' => Pages\CreateItem::route('/create'),
            // 'edit' => Pages\EditItem::route('/{record}/edit'),
        ];
    }
}
