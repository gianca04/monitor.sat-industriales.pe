<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseLocationResource\Pages;
use App\Filament\Resources\WarehouseLocationResource\RelationManagers;
use App\Models\WarehouseLocation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseLocationResource extends Resource
{
    protected static ?string $model = WarehouseLocation::class;

    protected static ?string $modelLabel = 'Ubicación de Almacén';
    protected static ?string $pluralModelLabel = 'Ubicaciones de Almacén';
    protected static ?string $navigationGroup = 'Gestión de inventario';
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Almacén')
                    ->relationship('warehouse', 'name')
                    ->required()
                    ->preload()
                    ->searchable(),
                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->maxLength(50)
                    ->visibleOn('edit'),
                Forms\Components\TextInput::make('area')
                    ->label('Área')
                    ->maxLength(100),
                Forms\Components\TextInput::make('rack')
                    ->label('Rack')
                    ->maxLength(50),
                Forms\Components\TextInput::make('shelf')
                    ->label('Estante')
                    ->maxLength(50),
                Forms\Components\TextInput::make('section')
                    ->label('Sección')
                    ->maxLength(50),
                Forms\Components\TextInput::make('bin')
                    ->label('Cajón / Gaveta')
                    ->maxLength(50),
                Forms\Components\Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Almacén')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('area')
                    ->label('Área')
                    ->searchable(),
                Tables\Columns\TextColumn::make('rack')
                    ->label('Rack')
                    ->searchable(),
                Tables\Columns\TextColumn::make('shelf')
                    ->label('Estante')
                    ->searchable(),
                Tables\Columns\TextColumn::make('section')
                    ->label('Sección')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bin')
                    ->label('Cajón / Gaveta')
                    ->searchable(),
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
            'index' => Pages\ListWarehouseLocations::route('/'),
            'create' => Pages\CreateWarehouseLocation::route('/create'),
            'edit' => Pages\EditWarehouseLocation::route('/{record}/edit'),
        ];
    }
}
