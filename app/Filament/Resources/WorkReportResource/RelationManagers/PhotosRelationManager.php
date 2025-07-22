<?php

namespace App\Filament\Resources\WorkReportResource\RelationManagers;

use Filament\Forms;
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
use Filament\Support\Enums\MaxWidth;
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

                Forms\Components\FileUpload::make('photo_path')
                    ->label('Fotografía')
                    ->image()
                    ->required()
                    ->downloadable()
                    ->directory('work-reports/photos')
                    ->visibility('public')
                    ->acceptedFileTypes(types: ['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5120) // 5MB
                    ->extraInputAttributes(['capture' => 'user'])
                    ->columnSpanFull()
                    ->helperText('Formatos soportados: JPEG, PNG, WebP. Tamaño máximo: 5MB'),

                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción de la evidencia')
                    ->required()
                    ->maxLength(500)
                    ->rows(3)
                    ->placeholder('Describe brevemente lo que se muestra en la fotografía...')
                    ->helperText('Máximo 500 caracteres'),

                Forms\Components\DateTimePicker::make('taken_at')
                    ->label('Fecha y hora de captura')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y H:i')
                    ->helperText('Fecha y hora en que se tomó la fotografía'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descripcion')
            ->columns([
                Stack::make([
                    // Columnas
                    Tables\Columns\ImageColumn::make('photo_path')
                        ->label('Evidencia')
                        ->height(200)

                        ->visibility('private')
                        ->checkFileExistence(false)
                        ->defaultImageUrl(url(path: '/images/no-image.png'))
                        ->extraAttributes(['class' => 'rounded-lg shadow-sm'])
                        ->alignCenter(), // Centra la imagen horizontalmente

                    Tables\Columns\TextColumn::make('descripcion')
                        ->label('Descripción')
                        ->limit(50)
                        ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                            $state = $column->getState();
                            if (strlen($state) <= 50) {
                                return null;
                            }
                            return $state;
                        })
                        ->searchable()
                        ->sortable()
                        ->size('m') // Texto un poco más pequeño para la descripción
                    , // Color secundario para diferenciación

                    Tables\Columns\TextColumn::make('taken_at')
                        ->label('Fecha de captura')
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
            ->filters([
                Tables\Filters\Filter::make('recent')
                    ->label('Últimas 24 horas')
                    ->query(fn(Builder $query): Builder => $query->where('taken_at', '>=', now()->subDay())),

                Tables\Filters\Filter::make('today')
                    ->label('Hoy')
                    ->query(fn(Builder $query): Builder => $query->whereDate('taken_at', today())),
            ])
            ->headerActions([

                Tables\Actions\CreateAction::make('take_photo')
                    ->label('Tomar Foto')
                    ->icon('heroicon-o-camera')
                    ->modalWidth(MaxWidth::Large)
                    ->form(function (Form $form) {
                        return $form->schema([
                            Forms\Components\FileUpload::make('photo_path')
                                ->label('Fotografía')
                                ->image()
                                ->required()
                                ->directory('work-reports/photos')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(5120) // 5MB
                                ->extraInputAttributes(['capture' => 'environment'])
                                ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 5MB.'),

                            Forms\Components\Textarea::make('descripcion')
                                ->label('Descripción de la evidencia')
                                ->required()
                                ->maxLength(500)
                                ->alignCenter()
                                ->rows(3)
                                ->placeholder('Describe brevemente lo que se muestra...'),

                            Forms\Components\DateTimePicker::make('taken_at')
                                ->label('Fecha y hora de captura')
                                ->default(now())
                                ->required()
                                ->alignCenter()
                                ->native(false)
                                ->displayFormat('d/m/Y H:i'),
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
                    ->modalWidth(MaxWidth::Large)
                    ->form(function (Form $form) {
                        return $form->schema([
                            Forms\Components\FileUpload::make('photo_path')
                                ->label('Fotografía')
                                ->image()
                                ->required()
                                ->directory('work-reports/photos')
                                ->visibility('public')
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->maxSize(5120) // 5MB
                                // Sin 'extraInputAttributes' para que abra la galería
                                ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 5MB.'),

                            Forms\Components\Textarea::make('descripcion')
                                ->label('Descripción de la evidencia')
                                ->required()
                                ->maxLength(500)
                                ->rows(3)
                                ->placeholder('Describe brevemente lo que se muestra...'),

                            Forms\Components\DateTimePicker::make('taken_at')
                                ->label('Fecha y hora de captura')
                                ->default(now())
                                ->required()
                                ->native(false)
                                ->displayFormat('d/m/Y H:i'),
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
                    //->modalContent(function (Photo $record): HtmlString {
                    //    $imageUrl = Storage::url($record->photo_path);
                    //    return new HtmlString("
                    //        <div class='space-y-4 text-center'>
                    //            <img src='{$imageUrl}' alt='Evidencia' class='h-auto max-w-full mx-auto rounded-lg shadow-lg' style='max-height: 70vh;'>
                    //            <div class='text-sm text-gray-600'>
                    //                <p><strong>Descripción:</strong> {$record->descripcion}</p>
                    //                <p><strong>Fecha de captura:</strong> {$record->taken_at->format('d/m/Y H:i')}</p>
                    //            </div>
                    //        </div>
                    //    ");
                    //})
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->modalWidth(MaxWidth::Large),

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
            ->defaultSort('taken_at', 'desc')
            ->poll('30s') // Actualizar cada 30 segundos
            ->emptyStateHeading('Sin evidencias')
            ->emptyStateDescription('No hay fotografías registradas para este reporte de trabajo.')
            ->emptyStateIcon('heroicon-o-camera')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Subir primera evidencia')
                    ->icon('heroicon-o-camera')
                    ->modalWidth(MaxWidth::Large),
            ]);
    }
}
