<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeesRelationManager extends RelationManager
{
    protected static string $relationship = 'supervisors';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('employee_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre completo')
                    ->getStateUsing(fn($record) => $record->full_name),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Documento'),
            ])
            ->filters([
                // Ejemplo de filtro para supervisores si tienes un campo 'is_supervisor'
                //Tables\Filters\TernaryFilter::make('is_supervisor')
                //    ->label('Solo supervisores'),
            ])
            ->headerActions([

                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make() // <-- Permite seleccionar estudiantes existentes
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['document_number', 'last_name', 'first_name']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('detach')
                    ->label('Desasociar')
                    ->action(fn ($record, $livewire) => $livewire->ownerRecord->supervisors()->detach($record->id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
