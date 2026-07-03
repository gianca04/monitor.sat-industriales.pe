<?php

namespace App\Filament\Resources\VisitResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Split;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VisitPhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'visitPhotos';
    protected static ?string $title = 'Evidencias Fotográficas';
    protected static ?string $modelLabel = 'Evidencia';
    protected static ?string $pluralModelLabel = 'Evidencias';
    protected static ?string $recordTitleAttribute = 'descripcion';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Split::make([
                    Forms\Components\FileUpload::make('photo_path')
                        ->label('Evidencia del Trabajo Realizado')
                        ->image()

                        ->downloadable()
                        ->directory('visits/photos')
                        ->visibility('public')
                        ->acceptedFileTypes(types: ['image/jpeg', 'image/png', 'image/webp'])
                        ->maxSize(25600) // 25MB
                        ->extraInputAttributes(['capture' => 'user'])
                        ->helperText('Formatos soportados: JPEG, PNG, WebP. Tamaño máximo: 25MB.'),

                    Forms\Components\RichEditor::make('descripcion')
                        ->label('Descripción de la evidencia del trabajo realizado')
                        ->maxLength(500)
                        ->placeholder('Describe brevemente lo que se muestra...')
                        ->helperText('Máximo 500 caracteres')
                        ->toolbarButtons([
                            'bold',
                            'h2',
                            'h3',
                            'orderedList',
                            'bulletList',
                            'redo',
                            'underline',
                            'undo',
                        ]),

                ])->from('md')
                    ->columnSpanFull()
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('visit_id')
            ->columns([
                Stack::make([
                    Panel::make([
                        Tables\Columns\Layout\Split::make([

                            Tables\Columns\ImageColumn::make('photo_path')
                                ->label('Evidencia')

                                ->width(170)   // Establece el ancho de la imagen en 160px
                                ->height(170)
                                ->visibility('private')
                                ->checkFileExistence(false)
                                ->defaultImageUrl(url(path: '/images/no-image.png'))
                                ->extraAttributes(['class' => 'rounded-lg shadow-sm']), // Centra la imagen horizontalmente
                        ])->from('md'),
                    ])->collapsed(false),

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
                'md' => 1,
                'xl' => 3,
            ])
            ->filters([
                //
            ])
            ->headerActions([

                Tables\Actions\CreateAction::make('take_photo')
                    ->label('Capturar')
                    ->icon('heroicon-o-camera')
                    ->modalWidth(MaxWidth::Full)
                    ->modalHeading('Tomar Fotografía')
                    ->form(function (Form $form) {
                        return $form->schema([
                            Split::make([
                                Forms\Components\FileUpload::make('photo_path')
                                    ->label('Evidencia')
                                    ->image()
                                    ->downloadable()
                                    ->directory('visits/photos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(25600) // 25MB
                                    ->extraInputAttributes(['capture' => 'environment'])
                                    ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                                Forms\Components\RichEditor::make('descripcion')
                                    ->label('Descripción de la evidencia')
                                    ->maxLength(500)
                                    ->placeholder('Describe brevemente lo que se muestra...')
                                    ->helperText('Máximo 500 caracteres')
                                    ->toolbarButtons([
                                        'bold',
                                        'h2',
                                        'h3',
                                        'orderedList',
                                        'bulletList',
                                        'redo',
                                        'underline',
                                        'undo',
                                    ]),

                            ])->from('md')
                                ->columnSpanFull()
                                ->columns(2),
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['visit_id'] = $this->ownerRecord->id;
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
                    ->label('Subir')
                    ->icon('heroicon-o-arrow-up-tray') // Icono diferente para distinguirlo

                    ->modalHeading('Subir Fotografía')
                    ->modalWidth(width: MaxWidth::Full)
                    ->form(function (Form $form) {
                        return $form->schema(components: [
                            Split::make([
                                Forms\Components\FileUpload::make('photo_path')
                                    ->label('Evidencia del Trabajo Realizado')
                                    ->image()
                                    ->previewable()
                                    ->directory('visits/photos')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(25600) // 25MB
                                    // Sin 'extraInputAttributes' para que abra la galería
                                    ->helperText('Formatos: JPEG, PNG, WebP. Tamaño máx: 25MB.'),

                                Forms\Components\RichEditor::make('descripcion')
                                    ->label('Descripción de la evidencia del trabajo realizado')
                                    ->maxLength(500)
                                    ->placeholder('Describe brevemente lo que se muestra...')
                                    ->helperText('Máximo 500 caracteres')
                                    ->toolbarButtons([
                                        'bold',
                                        'h2',
                                        'h3',
                                        'orderedList',
                                        'bulletList',
                                        'redo',
                                        'underline',
                                        'undo',
                                    ]),
                            ])->from('md')
                                ->columnSpanFull()
                                ->columns(2),
                        ]);
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['visit_id'] = $this->ownerRecord->id;
                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Evidencia subida')
                            ->body('La fotografía se ha registrado correctamente.')
                    ),
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
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
