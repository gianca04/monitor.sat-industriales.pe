<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequirementResource\Pages;
use App\Filament\Resources\RequirementResource\RelationManagers\RequirementListsRelationManager;
use App\Models\Requirement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;

class RequirementResource extends Resource
{
    protected static ?string $model = Requirement::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Requerimientos';

    protected static ?string $modelLabel = 'Requerimiento';

    protected static ?string $pluralModelLabel = 'Requerimientos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Requerimiento')
                    ->schema([
                        Forms\Components\Select::make('sub_client_id')
                            ->label('Sub-Cliente / Sede')
                            ->relationship('subClient', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('activity_name')
                            ->label('Nombre de la Actividad')
                            ->placeholder('ej: Mantenimiento preventivo, Instalación de tableros')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('activity_name')
                    ->label('Actividad')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('subClient.name')
                    ->label('Sub-Cliente / Sede')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('requirement_lists_count')
                    ->counts('requirementLists')
                    ->label('Total Ítems')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sub_client_id')
                    ->label('Sub-Cliente')
                    ->relationship('subClient', 'name'),
            ])
            ->actions([
                ActionGroup::make(
                    [
                        Tables\Actions\Action::make('export_async')
                            ->label('Exportar Excel')
                            ->icon('heroicon-o-document-arrow-down')
                            ->color('success')
                            ->action(function (\App\Models\Requirement $record) {
                                $exportService = app(\App\Services\RequirementExportService::class);
                                $tempPath = $exportService->exportAll($record);

                                return response()->download($tempPath, 'Requerimiento_'.$record->id.'.xlsx')->deleteFileAfterSend(true);
                            }),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                    ]
                ),
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
            RequirementListsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequirements::route('/'),
            'create' => Pages\CreateRequirement::route('/create'),
            'edit' => Pages\EditRequirement::route('/{record}/edit'),
        ];
    }
}
