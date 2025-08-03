<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Resources\WorkReportResource\Pages\CreateWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\EditWorkReport;
use App\Filament\Resources\WorkReportResource\Pages\ListWorkReports;
use App\Filament\Resources\WorkReportResource\Pages\ViewWorkReport;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Reporte')
                    ->searchable()
                    ->extraAttributes(['class' => 'font-bold'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('employee.first_name')
                    ->label('Supervisor')
                    ->formatStateUsing(fn($record) => $record->employee->first_name . ' ' . $record->employee->last_name)
                    ->searchable(['first_name', 'last_name'])
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('employee')
                    ->label('Colaborador')
                    ->relationship('employee', 'first_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn() => session('filter_project_id'))
                    ->placeholder('Todos los proyectos'),

            ])
            ->headerActions([

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
                Tables\Actions\Action::make('manage_all')
                    ->label('Todos los reportes')
                    ->icon('heroicon-o-table-cells')
                    ->color('info')
                    ->tooltip('Ver y gestionar todos los reportes del proyecto en la vista completa')
                    ->action(function () {
                        // Guardar el project_id en la sesión para filtros
                        session(['filter_project_id' => $this->ownerRecord->id]);

                        // Redirigir al TimesheetResource index
                        return redirect(route('filament.dashboard.resources.work-reports.index'));
                    }),
            ])
            ->actions([


                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye')
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->color('danger'),

                RelationManagerAction::make('photos-relation-manager')
                    ->label('Ver fotografías')
                    ->slideOver(true)
                    ->relationManager(PhotosRelationManager::make()),


                Tables\Actions\Action::make('generate_report')
                    ->label('Generar PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document')
                    ->url(fn($action) => route('work-report.pdf', $action->getRecord()->id))
                    ->openUrlInNewTab()
                    ->visible(fn($action) => $action->getRecord()->photos()->count() > 0)
                    ->tooltip('Generar reporte PDF del trabajo realizado'),

                Tables\Actions\Action::make('generate_word_report')
                    ->label('Generar Word')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn($action) => route('work-report.word', $action->getRecord()->id))
                    ->openUrlInNewTab()
                    ->visible(fn($action) => $action->getRecord()->photos()->count() > 0)
                    ->tooltip('Generar reporte Word del trabajo realizado'),

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
