<?php

namespace App\Filament\Resources\WorkReportResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Concerns\HasMaxHeight;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use App\Models\Photo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Split;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Support\HtmlString;

class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';
    protected static ?string $title = 'Evidencias Fotográficas';
    protected static ?string $modelLabel = 'Evidencia';
    protected static ?string $pluralModelLabel = 'Evidencias';
    protected static ?string $recordTitleAttribute = 'descripcion';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Split::make([
                    Forms\Components\FileUpload::make('before_work_photo_path')
                        ->label('Fotografía del trabajo previo')
                        ->image()
                        ->downloadable()
                        ->directory('work-reports/photos')
                        ->visibility('public')
                        ->acceptedFileTypes(types: ['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(25600) // 25MB
                        ->extraInputAttributes(['capture' => 'user'])
                        ->columnSpanFull()
                        ->helperText('Formatos soportados: JPEG, PNG, WebP. Tamaño máximo: 25MB.'),

                    Forms\Components\RichEditor::make('before_work_descripcion')
                        ->label('Descripción de la evidencia antes del trabajo')
                        ->maxLength(500)
                        ->helperText('Máximo 500 caracteres'),

                ])->from('md')
                    ->columnSpanFull()
                    ->columns(2),

                Split::make([

                    Forms\Components\FileUpload::make('photo_path')
                        ->label('Fotografía del trabajo realizado')
                        ->image()
                        ->downloadable()
                        ->directory('work-reports/photos')
                        ->visibility('public')
                        ->acceptedFileTypes(types: ['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(25600) // 25MB
                        ->extraInputAttributes(['capture' => 'user'])
                        ->helperText('Formatos soportados: JPEG, PNG, WebP. Tamaño máximo: 25MB.'),

                    Forms\Components\RichEditor::make('descripcion')
                        ->label('Descripción de la evidencia del trabajo realizado')
                        ->maxLength(500)
                        ->helperText('Máximo 500 caracteres'),

                ])->from('md')
                    ->columnSpanFull()
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                Stack::make([
                    Panel::make([
                        Tables\Columns\Layout\Split::make([

                            Tables\Columns\ImageColumn::make('before_work_photo_path')
                                ->label('Evidencia')
                                ->width(130)   // Establece el ancho de la imagen en 80px
                                ->height(130)
                                ->visibility('private')
                                ->checkFileExistence(false)
                                ->defaultImageUrl(url(path: '/images/no-image.png'))
                                ->extraAttributes(['class' => 'rounded-lg shadow-sm']),
                            Tables\Columns\ImageColumn::make('photo_path')
                                ->label('Evidencia')
                                ->width(130)   // Establece el ancho de la imagen en 80px
                                ->height(130)
                                ->visibility('private')
                                ->checkFileExistence(false)
                                ->defaultImageUrl(url(path: '/images/no-image.png'))
                                ->extraAttributes(['class' => 'rounded-lg shadow-sm']), // Centra la imagen horizontalmente

                        ])->from('md'),
                    ])->collapsed(false),

                    Tables\Columns\TextColumn::make('before_work_descripcion')
                        ->searchable()
                        ->size('m')
                        ->lineClamp(2)
                        ->formatStateUsing(fn(string $state): HtmlString => new HtmlString($state)),


                    Tables\Columns\TextColumn::make('descripcion')
                        ->searchable()
                        ->size('m')
                        ->lineClamp(2)
                        ->formatStateUsing(fn(string $state): HtmlString => new HtmlString($state)),

                    Tables\Columns\TextColumn::make('created_at')
                        ->label('Fecha de creación')
                        ->dateTime('d/m/Y H:i')
                        ->sortable()
                        ->icon('heroicon-o-calendar')
                        ->toggleable()
                        ->size('xs') // Tamaño consistente
                        ->color('gray'), // Resalta la fecha principal
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([])
            ->headerActions([

                Tables\Actions\CreateAction::make('take_photo')
                    ->label('Tomar Foto')

                    ->icon('heroicon-o-camera')
                    ->modalWidth(MaxWidth::Full)
                    ->modalHeading('Tomar Fotografía')
                    ->form(function (Form $form) {
                        return $form->schema([
                            Split::make([
                                Forms\Components\FileUpload::make('before_work_photo_path')
                                    ->label('Fotografía del trabajo previo')
                                    ->image()
                                    ->downloadable()
                                    ->directory('work-reports/photos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(25600) // 25MB
                                    ->extraInputAttributes(['capture' => 'environment'])
                                    ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                                Forms\Components\RichEditor::make('before_work_descripcion')
                                    ->label('Descripción de la evidencia antes del trabajo')
                                    ->maxLength(500)
                                    ->placeholder('Describe brevemente lo que se muestra...'),

                            ])->from('md')
                                ->columnSpanFull()
                                ->columns(2),

                            Split::make([

                                Forms\Components\FileUpload::make('photo_path')
                                    ->label('Fotografía del trabajo culminado')
                                    ->image()
                                    ->downloadable()
                                    ->directory('work-reports/photos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(25600) // 25MB
                                    ->extraInputAttributes(['capture' => 'environment'])
                                    ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                                Forms\Components\RichEditor::make('descripcion')
                                    ->label('Descripción de la evidencia')
                                    ->maxLength(500)
                                    ->placeholder('Describe brevemente lo que se muestra...'),

                            ])->from('md')
                                ->columnSpanFull()
                                ->columns(2),
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['work_report_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Evidencia subida')
                            ->body('La fotografía se ha registrado correctamente.')
                    ),

                // Botón para SUBIR DE GALERÍA (abre el selector de archivos)
                Tables\Actions\CreateAction::make('upload_from_gallery')
                    ->label('Subir de Galería')
                    ->icon('heroicon-o-arrow-up-tray') // Icono diferente para distinguirlo

                    ->modalHeading('Subir Fotografía')
                    ->modalWidth(width: MaxWidth::Full)
                    ->form(function (Form $form) {
                        return $form->schema(components: [
                            Forms\Components\FileUpload::make('before_work_photo_path')
                                ->label('Fotografía')
                                ->image()

                                ->previewable()
                                ->directory('work-reports/photos')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(25600) // 25MB
                                // Sin 'extraInputAttributes' para que abra la galería
                                ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                            Forms\Components\FileUpload::make('photo_path')
                                ->label('Fotografía')
                                ->image()

                                ->previewable()
                                ->directory('work-reports/photos')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(25600) // 25MB
                                // Sin 'extraInputAttributes' para que abra la galería
                                ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                            Forms\Components\RichEditor::make('before_work_descripcion')
                                ->label('Descripción de la evidencia')

                                ->maxLength(500)
                                ->placeholder('Describe brevemente lo que se muestra...'),

                            Forms\Components\RichEditor::make('descripcion')
                                ->label('Descripción de la evidencia')

                                ->maxLength(500)
                                ->placeholder('Describe brevemente lo que se muestra...'),


                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['work_report_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Evidencia subida')
                            ->body('La fotografía se ha registrado correctamente.')
                    ),

                Action::make('generate_report')
                    ->label('Generar PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('danger')
                    ->icon('heroicon-o-document')
                    ->url(fn() => route('work-report.pdf', $this->ownerRecord->id))
                    ->openUrlInNewTab()
                    ->visible(fn() => $this->ownerRecord->photos()->count() > 0)
                    ->tooltip('Generar reporte PDF del trabajo realizado'),
                Action::make('generate_word_report')
                    ->label('Generar Word')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn() => route('work-report.word', $this->ownerRecord->id))
                    ->openUrlInNewTab()
                    ->visible(fn() => $this->ownerRecord->photos()->count() > 0)
                    ->tooltip('Generar reporte Word del trabajo realizado'),
            ])
            ->actions([

                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalWidth(MaxWidth::Full)

                    ->modalHeading('Ver Fotografías')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading('Editar Fotografías')
                    ->modalWidth(MaxWidth::Full),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar evidencia')
                    ->modalDescription('¿Estás seguro de que deseas eliminar esta evidencia? Esta acción no se puede deshacer.')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Evidencia eliminada')
                            ->body('La fotografía se ha eliminado correctamente.')
                    ),


            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar evidencias seleccionadas')
                        ->modalDescription('¿Estás seguro de que deseas eliminar las evidencias seleccionadas? Esta acción no se puede deshacer.'),

                    Action::make('bulk_download')
                        ->label('Descargar seleccionadas')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function ($records) {
                            // Aquí podrías implementar la descarga en ZIP
                            Notification::make()
                                ->info()
                                ->title('Funcionalidad en desarrollo')
                                ->body('La descarga masiva estará disponible próximamente.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s') // Actualizar cada 30 segundos
            ->emptyStateHeading('Sin evidencias')
            ->emptyStateDescription('No hay fotografías registradas para este reporte de trabajo.')
            ->emptyStateIcon('heroicon-o-camera')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Subir primera evidencia')
                    ->icon('heroicon-o-camera')
                    ->modalWidth(width: MaxWidth::Full),
            ]);
    }
}
