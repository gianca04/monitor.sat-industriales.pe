<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\WorkReportResource\Pages\CreateWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\EditWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\ListWorkReports;
use App\Filament\Resources\WorkReportResource\Pages\ViewWorkReport;
use App\Filament\Resources\WorkReportResource\RelationManagers\PhotosRelationManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;



class WorkReportsRelationManager extends RelationManager
{
    protected static ?string $title = 'Reportes de Trabajo';

    protected static ?string $modelLabel = 'Reporte de Trabajo';
    protected static ?string $pluralModelLabel = 'Reportes de Trabajo';
    protected static string $relationship = 'WorkReports';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            PhotosRelationManager::class,
        ];
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Supervisor')
                    ->formatStateUsing(fn($record) => $record->employee->first_name . ' ' . $record->employee->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Reporte')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('photos_count')
                    ->label('Evidencias')
                    ->counts('photos')
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        $state == 0 => 'danger',
                        $state < 5 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),

            ])
            ->headerActions([

                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('manage_all')
                    ->label('Gestionar Todos los reportes')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->tooltip('Ver y gestionar todos los reportes del proyecto en la vista completa')
                    ->action(function () {
                        // Guardar el project_id en la sesión para filtros
                        session(['filter_project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource index
                        return redirect(route('filament.dashboard.resources.work-reports.index'));
                    }),
                Tables\Actions\Action::make('create_advanced')
                    ->label('Crear reporte')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->tooltip('Ir al formulario completo de tareos con todas las funcionalidades')
                    ->action(function () {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource create
                        return redirect(route('filament.dashboard.resources.work-reports.create'));
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_advanced')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver en la vista completa con todas las relaciones')
                    ->action(function ($record) {
                        // Redirigir al WorkReportResource view
                        return redirect(route('filament.dashboard.resources.work-reports.view', $record));
                    }),
                Tables\Actions\Action::make('edit_advanced')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->tooltip('Editar en el formulario completo con todas las funcionalidades')
                    ->action(function ($record) {
                        // Guardar el project_id en la sesión
                        session(['project_id' => $this->ownerRecord->id]);

                        // Redirigir al WorkReportResource edit
                        return redirect(route('filament.dashboard.resources.work-reports.edit', $record));
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No hay reportes registrados')
            ->emptyStateDescription('Comienza creando el primer reporte para este proyecto.')
            ->emptyStateIcon('heroicon-o-wrench-screwdriver')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
