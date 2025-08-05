<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TextInputFilter;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Split;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    use Translatable;

    protected static ?string $model = Client::class;

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Gestión de clientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Split::make([
                    Forms\Components\Section::make('Información principal')
                        ->description('Datos generales del cliente')
                        ->icon('heroicon-o-identification')
                        ->schema([

                            Forms\Components\TextInput::make('business_name')
                                ->label('Razón social')
                                ->placeholder('Nombre de la empresa o persona')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->prefixIcon('heroicon-o-building-office-2'),
                            Forms\Components\Select::make('document_type')
                                ->label('Tipo de documento')
                                ->options([
                                    'RUC' => 'RUC',
                                    'DNI' => 'DNI',
                                    'FOREIGN_CARD' => 'Carné de Extranjería',
                                    'PASSPORT' => 'Pasaporte',
                                ])
                                ->required()
                                ->searchable()
                                ->placeholder('Selecciona tipo de documento')
                                ->columnSpan(1)
                                ->alphaNum()
                                ->prefixIcon('heroicon-o-identification'),
                            Forms\Components\TextInput::make('document_number')
                                ->label('Número de documento')
                                ->placeholder('Ej: 12345678901')
                                ->required()
                                ->maxLength(11)
                                ->minLength(8)
                                ->alphaNum()
                                ->columnSpan(1)
                                ->prefixIcon('heroicon-o-hashtag'),
                            Forms\Components\Select::make('person_type')
                                ->label('Tipo de persona')
                                ->options([
                                    'Natural Person' => 'Persona Natural',
                                    'Legal Entity' => 'Persona Jurídica',
                                ])
                                ->required()
                                ->searchable()
                                ->placeholder('Selecciona tipo de persona')
                                ->columnSpan(1)
                                ->prefixIcon('heroicon-o-user-group'),
                            Forms\Components\Textarea::make('description')
                                ->label('Descripción')
                                ->placeholder('Descripción del cliente')
                                ->columnSpanFull()
                                ->rows(2)
                                ->autosize(),

                        ])
                        ->columns(2),

                    Forms\Components\Section::make('Contacto')
                        ->icon('heroicon-o-phone')
                        ->description('Información de contacto y dirección')
                        ->schema([
                            Forms\Components\TextInput::make('address')
                                ->label('Dirección')
                                ->placeholder('Dirección fiscal o comercial')
                                ->maxLength(255)
                                ->columnSpan(2)
                                ->prefixIcon('heroicon-o-map-pin'),
                            Forms\Components\TextInput::make('contact_phone')
                                ->label('Teléfono de contacto')
                                ->placeholder('Ej: +51 999 999 999')
                                ->tel()
                                ->maxLength(9)
                                ->minLength(7)
                                ->columnSpan(1)
                                ->prefixIcon('heroicon-o-phone'),
                            Forms\Components\TextInput::make('contact_email')
                                ->label('Correo electrónico')
                                ->placeholder('correo@ejemplo.com')
                                ->email()
                                ->maxLength(255)
                                ->columnSpan(1)
                                ->prefixIcon('heroicon-o-envelope'),
                            Forms\Components\FileUpload::make('logo')
                                ->label('Logo')
                                ->image()
                                ->imageEditor()
                                ->directory('logos')
                                ->columnSpan(2)
                                ->hint('Sube el logo de la empresa')
                                ->panelLayout('integrated')

                                ->directory('uploads/users')
                                ->previewable(true),
                        ])
                        ->columns(2),
                ])
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('subClients')
                    ->label('Subclientes')
                    ->relationship('subClients')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del subcliente')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->placeholder('Dirección del subcliente')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-map-pin'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(500)
                            ->autosize()
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('contactData')
                            ->label('Datos de contacto')
                            ->relationship('contactData')
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo electrónico')
                                    ->email()
                                    ->maxLength(255)
                                    ->placeholder('correo@ejemplo.com'),

                                Forms\Components\TextInput::make('phone_number')
                                    ->label('Teléfono')
                                    ->tel()
                                    ->maxLength(15)
                                    ->placeholder('Ej: +51 999 999 999'),

                                Forms\Components\TextInput::make('contact_name')
                                    ->label('Nombre de contacto')
                                    ->maxLength(255)
                                    ->placeholder('Nombre del contacto'),

                            ]),

                        /*Forms\Components\Section::make('Coordenadas geográficas')
                            ->columns(1)
                            ->schema([
                                \App\Forms\Components\ubicacion::make('location')
                                    ->label('Ubicación en el mapa'),

                            ]),
*/
                    ])
                    ->createItemButtonLabel('Agregar subcliente')
                    ->columns(2)
                    ->collapsible()
                    ->grid(2)
                    ->columnSpanFull()
                    ->addActionLabel('Nuevo'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->height(40)
                    ->width(40)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('business_name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office-2')
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('document_type')
                    ->label('Tipo Doc.')
                    ->colors([
                        'primary' => 'RUC',
                        'success' => 'DNI',
                        'warning' => 'FOREIGN_CARD',
                        'info' => 'PASSPORT',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('N° Documento')
                    ->sortable()
                    ->icon('heroicon-o-hashtag'),
                Tables\Columns\BadgeColumn::make('person_type')
                    ->label('Tipo Persona')
                    ->colors([
                        'primary' => 'Natural Person',
                        'secondary' => 'Legal Entity',
                    ])
                    ->icons([
                        'heroicon-o-user' => 'Natural Person',
                        'heroicon-o-user-group' => 'Legal Entity',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->address)
                    ->icon('heroicon-o-map-pin'),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Teléfono')
                    ->icon('heroicon-o-phone'),
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Correo')
                    ->icon('heroicon-o-envelope'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('success')
                    ->icon('heroicon-o-calendar-days')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color('warning')
                    ->icon('heroicon-o-arrow-path')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('document_type')
                    ->label('Tipo de documento')
                    ->options([
                        'RUC' => 'RUC',
                        'DNI' => 'DNI',
                        'FOREIGN_CARD' => 'Carné de Extranjería',
                        'PASSPORT' => 'Pasaporte',
                    ]),
                Tables\Filters\SelectFilter::make('person_type')
                    ->label('Tipo de persona')
                    ->options([
                        'Natural Person' => 'Persona Natural',
                        'Legal Entity' => 'Persona Jurídica',
                    ]),

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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Puedes agregar RelationManagers aquí si los necesitas
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
