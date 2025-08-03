<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Forms\Components\Select;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeesRelationManager extends RelationManager
{
    protected static ?string $title = 'Supervisores';

    protected static ?string $modelLabel = 'Supervisor';
    protected static ?string $pluralModelLabel = 'Supervisores';

    protected static string $relationship = 'supervisors';

    public function form(Form $form): Form
    {
        return $form
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
                    ]),

                Forms\Components\Select::make('position_id')
                    ->label('Cargo')
                    ->relationship('position', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->placeholder('Seleccionar cargo')

                    ->createOptionForm([
                        Forms\Components\Section::make('Información del cargo')
                            ->description('Datos generales del cargo')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required(),
                            ])
                            ->columns(2),
                    ]),

                Forms\Components\Toggle::make('active')
                    ->label('Activo')
                    ->helperText('Marca esta opción para activar al colaborador y permitirle iniciar sesión.')
                    ->default(true)
                    ->live(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nombre completo')
                    ->getStateUsing(fn($record) => $record->full_name)
                    ->searchable()
                    ->sortable()
                    ->extraAttributes(['class' => 'font-bold']),
            ])
            ->filters([
                // Ejemplo de filtro para supervisores si tienes un campo 'is_supervisor'
                //Tables\Filters\TernaryFilter::make('is_supervisor')
                //    ->label('Solo supervisores'),
            ])
            ->headerActions([

                Tables\Actions\CreateAction::make()
                    ->label('Crear Colaborador y asociar'),

                Tables\Actions\AttachAction::make()
                    // Precarga el Select, lo cual es bueno

                    // Define el campo Select dentro del modal
                    ->recordSelect(
                        fn(Select $select) => $select
                            // Le indicamos a Filament en qué columnas buscar
                            ->searchable(['document_number', 'last_name', 'first_name'])
                            ->getSearchResultsUsing(function (string $query) {
                                return Employee::query()
                                    ->where('document_number', 'like', "%{$query}%")
                                    ->orWhere('last_name', 'like', "%{$query}%")
                                    ->orWhere('first_name', 'like', "%{$query}%")
                                    ->get()
                                    ->mapWithKeys(fn(Employee $employee) => [
                                        $employee->id => $employee->full_name
                                    ]);
                            })
                            ->getOptionLabelUsing(fn($value) => Employee::find($value)?->full_name ?? $value)
                            // Configuración adicional para el select
                            ->placeholder('Seleccionar empleado...')
                            ->native(false),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('detach')
                    ->label('Desasociar')
                    ->action(fn($record, $livewire) => $livewire->ownerRecord->supervisors()->detach($record->id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DissociateBulkAction::make(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
