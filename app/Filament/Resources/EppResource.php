<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EppResource\Pages;
use App\Filament\Resources\EppResource\RelationManagers;
use App\Models\Epp;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EppResource extends Resource
{
    protected static ?string $model = Epp::class;

    protected static ?string $modelLabel = 'EPP';
    protected static ?string $pluralModelLabel = 'EPPs';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // Columna Izquierda: Galería de Fotos y Estado (4 columnas)
                        Forms\Components\Group::make()
                            ->columnSpan(['lg' => 4])
                            ->schema([
                                Forms\Components\FileUpload::make('photos')
                                    ->label('Fotos')
                                    ->multiple()
                                    ->image()
                                    ->panelLayout('grid')
                                    ->reorderable()
                                    ->openable()
                                    ->directory('epp-photos'),
                                Forms\Components\Toggle::make('active')
                                    ->label('Producto Activo')
                                    ->default(true)
                                    ->required(),
                            ]),

                        // Columna Derecha: Campos de Información (8 columnas)
                        Forms\Components\Group::make()
                            ->columnSpan(['lg' => 8])
                            ->schema([
                                Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('model')
                                        ->label('Modelo')
                                        ->maxLength(100),
                                ]),
                                Grid::make(2)->schema([
                                    Forms\Components\Select::make('brand_id')
                                        ->label('Marca')
                                        ->relationship('brand', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Nombre de la Marca')
                                                ->required()
                                                ->maxLength(100),
                                        ]),
                                    Forms\Components\Select::make('category_id')
                                        ->label('Categoría')
                                        ->searchable()
                                        ->options(\App\Models\Category::all()->pluck('name', 'id'))
                                        ->live()
                                        ->afterStateHydrated(function (Forms\Components\Select $component, ?\App\Models\Epp $record) {
                                            if ($record) {
                                                $firstSub = $record->subcategories()->first();
                                                if ($firstSub) {
                                                    $component->state($firstSub->category_id);
                                                }
                                            }
                                        })
                                        ->dehydrated(false)
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('name')
                                                ->label('Nombre de la Categoría')
                                                ->required()
                                                ->maxLength(100),
                                            Forms\Components\Textarea::make('description')
                                                ->label('Descripción'),
                                        ])
                                        ->createOptionUsing(function (array $data): int {
                                            return \App\Models\Category::create($data)->id;
                                        }),
                                ]),
                                Grid::make(2)->schema([
                                    Forms\Components\Select::make('subcategories')
                                        ->label('Subcategorías')
                                        ->searchable()
                                        ->multiple()
                                        ->relationship('subcategories', 'name', modifyQueryUsing: function (Builder $query, Forms\Get $get) {
                                            $categoryId = $get('category_id');
                                            if ($categoryId) {
                                                $query->where('category_id', $categoryId);
                                            }
                                        })
                                        ->preload()
                                        ->required()
                                        ->createOptionForm(function (Forms\Get $get) {
                                            return [
                                                Forms\Components\Select::make('category_id')
                                                    ->label('Categoría')
                                                    ->relationship('category', 'name')
                                                    ->default($get('category_id'))
                                                    ->required()
                                                    ->preload()
                                                    ->searchable(),
                                                Forms\Components\TextInput::make('name')
                                                    ->label('Nombre de la Subcategoría')
                                                    ->required()
                                                    ->maxLength(100),
                                            ];
                                        }),
                                    Forms\Components\Select::make('certifications')
                                        ->label('Certificaciones')
                                        ->multiple()
                                        ->relationship('certifications', 'code')
                                        ->preload(),
                                ]),
                                Forms\Components\Textarea::make('description')
                                    ->label('Descripción')
                                    ->rows(3),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photos')
                    ->label('Fotos')
                    ->circular()
                    ->stacked(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->searchable(),
                Tables\Columns\ToggleColumn::make('active')
                    ->label('Activo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stock')
                    ->badge()
                    ->color(
                        fn(Epp $record, \App\Services\InventoryService $inventoryService) =>
                        $inventoryService->isBelowMinimum($record) ? 'danger' : 'success'
                    ),
                Tables\Columns\IconColumn::make('requires_replenishment')
                    ->label('Abastecido')
                    ->boolean()
                    ->trueIcon('heroicon-o-exclamation-triangle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(
                        fn(Epp $record, \App\Services\InventoryService $inventoryService) =>
                        $inventoryService->requiresReplenishment($record)
                    ),
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
                ActionGroup::make([
                    ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\StocksRelationManager::class,
            RelationManagers\StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpps::route('/'),
            'create' => Pages\CreateEpp::route('/create'),
            'edit' => Pages\EditEpp::route('/{record}/edit'),
        ];
    }
}
