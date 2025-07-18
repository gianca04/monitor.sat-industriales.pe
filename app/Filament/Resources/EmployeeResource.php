<?php

namespace App\Filament\Resources;

use App\Filament\Exports\EmployeeExporter;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Filament\Resources\EmployeeResource\RelationManagers;
use App\Models\Employee;
use Filament\Actions\Exports\Enums\Contracts\ExportFormat;
use Filament\Forms;
use App\Filament\Exports\ProductExporter;
use Filament\Forms\Components\Tabs;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Reactive;
use PhpParser\Node\Stmt\Label;

class EmployeeResource extends Resource

{
    use Translatable;

    protected static ?string $model = Employee::class;

    protected static ?string $pluralModelLabel = 'Colaboradores';
    protected static int $globalSearchResultsLimit = 10;
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'document_number'];  // Verifica que estos atributos sean los más relevantes para la búsqueda
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Correo' => $record->user->email,
            'Nombre' => $record->first_name,
        ];
    }
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        // Optimiza la consulta, asegurando que solo cargue lo necesario
        return parent::getGlobalSearchEloquentQuery()
            ->with('user'); // Selecciona solo las columnas necesarias del modelo Employee
    }

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Recursos Humanos';



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('MainTabs')
                    ->tabs([
                        Tabs\Tab::make('Información del Empleado')
                            ->icon('heroicon-m-user')

                            ->columns(2)
                            ->schema([

                                Forms\Components\Select::make('document_type')
                                    ->label('Tipo de Documento')
                                    ->options([
                                        'DNI' => 'DNI',
                                        'PASSPORT' => 'Pasaporte',
                                        'FOREIGN_CARD' => 'Carnet de Extranjería',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->placeholder('Seleccionar tipo de documento'),

                                Forms\Components\TextInput::make('document_number')
                                    ->label('Número de Documento')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->minLength(8)
                                    ->maxLength(12)
                                    ->numeric()
                                    ->placeholder('Ingresar número de documento'),

                                Forms\Components\TextInput::make('first_name')
                                    ->label('Nombres')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('Ingresar primer nombre')
                                    ->autocomplete('given-name'),

                                Forms\Components\TextInput::make('last_name')
                                    ->label('Apellido')
                                    ->required()
                                    ->maxLength(40)
                                    ->placeholder('Ingresar apellido')
                                    ->autocomplete('family-name'),

                                Forms\Components\TextInput::make('address')
                                    ->label('Dirección')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ingresar dirección'),

                                Forms\Components\DatePicker::make('date_contract')
                                    ->label('Fecha de Contrato')
                                    ->required()
                                    ->maxDate(now())
                                    ->placeholder('Seleccionar fecha de contrato'),

                                Forms\Components\DatePicker::make('date_birth')
                                    ->label('Fecha de Nacimiento')
                                    ->required()
                                    ->maxDate(now()->subYears(18))
                                    ->placeholder('Seleccionar fecha de nacimiento'),

                                Forms\Components\Select::make('sex')
                                    ->label('Sexo del colaborador')
                                    ->required()
                                    ->native(false)
                                    ->options([
                                        'male' => 'Masculino',
                                        'female' => 'Femenino',
                                        'other' => 'No específicado',
                                    ])
                            ])
                            ->columnSpan('full'),

                        Tabs\Tab::make('Información del Usuario')
                            ->icon('heroicon-m-lock-closed')

                            ->columns(2)
                            ->schema([

                                Section::make('')
                                    ->relationship('user')
                                    ->columns(2)
                                    ->schema([
                                        // ...
                                        Toggle::make('is_active')
                                            ->label('Crear Usuario y Habilitar Acceso')
                                            ->helperText('Marca esta opción para crear un usuario asociado a este empleado y permitirle iniciar sesión.')
                                            ->live() // Hace que el formulario reaccione al cambio de este toggle
                                            ->default(false), // Por defecto, no crear usuario

                                        TextInput::make('name')
                                            ->label('Nombre de Usuario')
                                            ->required(fn(Forms\Get $get): bool => $get('is_active')) // Requerido solo si is_active es true
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->visible(fn(Forms\Get $get): bool => $get('is_active')), // Visible solo si is_active es true

                                        TextInput::make('email')
                                            ->label('Correo Electrónico')
                                            ->required(fn(Forms\Get $get): bool => $get('is_active')) // Requerido solo si is_active es true
                                            ->email()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true) // Asegura que el email sea único, ignora el registro actual en edición
                                            ->visible(fn(Forms\Get $get): bool => $get('is_active')), // Visible solo si is_active es true

                                        Forms\Components\Select::make('roles')
                                            ->relationship('roles', 'name')
                                            ->multiple()
                                            ->preload()
                                            ->searchable()
                                            ->required(fn(Forms\Get $get): bool => $get('is_active')) // Requerido solo si is_active es true
                                            ->visible(fn(Forms\Get $get): bool => $get('is_active')), // Visible solo si is_active es true

                                        TextInput::make('password')
                                            ->label('Contraseña')
                                            ->password()
                                            ->dehydrateStateUsing(fn(string $state): string => Hash::make($state)) // Hashea la contraseña automáticamente
                                            ->dehydrated(fn(?string $state): bool => filled($state)) // Solo guarda si hay algo en el campo
                                            ->required(fn(string $operation): bool => $operation === 'create') // Requerido solo en 'create'
                                            ->visible(fn(Forms\Get $get): bool => $get('is_active')) // Visible solo si is_active es true
                                            ->confirmed() // Requiere un campo de confirmación
                                            ->maxLength(255),

                                        TextInput::make('password_confirmation')
                                            ->password()
                                            ->label('Confirmar Contraseña')
                                            ->visible(fn(Forms\Get $get): bool => $get('is_active')) // Visible solo si is_active es true
                                            ->required(fn(Forms\Get $get, string $operation): bool => $get('is_active') && $operation === 'create'), // Requerido solo si is_active es true y en 'create'
                                    ]),
                            ])

                            ->columnSpan('full'),
                    ])
                    ->columnSpan('full'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Nombres')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-identification'),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->sortable()
                    ->icon('heroicon-o-identification')
                    ->searchable(),

                Tables\Columns\TextColumn::make('document_number')
                    ->label('N° Documento')
                    ->searchable()
                    ->icon('heroicon-o-hashtag'),

                Tables\Columns\TextColumn::make('document_type')
                    ->colors([
                        'success' => 'DNI',
                        'warning' => 'FOREIGN_CARD',
                        'info' => 'PASSPORT',
                    ])
                    ->badge()
                    ->label('Tipo de Doc'),

                Tables\Columns\TextColumn::make('date_birth')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->icon('heroicon-o-cake')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_contract')
                    ->label('Fecha de Contrato')
                    ->date()
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('address')
                    ->tooltip(fn($record) => $record->address)
                    ->label('Dirección')
                    ->icon('heroicon-o-map-pin')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->icon('heroicon-o-envelope')
                    ->label('Nombre de ususario'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Actualizado')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

            ])
            ->headerActions(
                [
                    ExportAction::make()
                        ->exporter(EmployeeExporter::class)

                ]
            )
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
