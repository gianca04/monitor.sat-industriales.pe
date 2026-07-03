<?php

namespace App\Filament\Resources\WarehouseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $modelLabel = 'Ubicación';
    protected static ?string $pluralModelLabel = 'Ubicaciones';
    protected static ?string $title = 'Ubicaciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
