<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Filament\Resources\VisitResource\RelationManagers;
use App\Filament\Resources\VisitResource\RelationManagers\VisitPhotosRelationManager;
use App\Forms\Components\ProjectClientSelect;
use App\Models\Employee;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Saade\FilamentAutograph\Forms\Components\SignaturePad;

class VisitResource extends Resource
{
    use Translatable;
    protected static ?string $model = Visit::class;
    protected static ?string $title = 'Visitas';
    protected static ?string $modelLabel = 'Visita';
    protected static ?string $pluralModelLabel = 'Visitas';
    protected static ?string $singularModelLabel = 'Visita';
    protected static ?string $navigationGroup = 'Control de operaciones';
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('MainTabs')
                    ->tabs([
                        // INICIO DE TAB DE INFORMACIÓN GENERAL
                        Tab::make('Información general')
                            ->icon('heroicon-o-information-circle')
                            ->columns(2)
                            ->schema([


                                // INICIO DE SELECT DE EMPLEADO
                                Forms\Components\Select::make('employee_id')
                                    ->default(fn() => Auth::user()?->employee_id)->required()
                                    ->columns(2)
                                    ->reactive()
                                    ->prefixIcon('heroicon-m-user')
                                    ->label('Supervisor / Técnico') // Título para el campo 'Empleado'
                                    ->options(
                                        function (callable $get) {
                                            return Employee::query()
                                                ->select('id', 'first_name', 'last_name', 'document_number')
                                                ->when($get('search'), function ($query, $search) {
                                                    $query->where('first_name', 'like', "%{$search}%")
                                                        ->orWhere('last_name', 'like', "%{$search}%")
                                                        ->orWhere('document_number', 'like', "%{$search}%");
                                                })
                                                ->get()
                                                ->mapWithKeys(function ($employee) {
                                                    return [$employee->id => $employee->full_name];
                                                })
                                                ->toArray();
                                        }
                                    )
                                    ->searchable() // Activa la búsqueda asincrónica
                                    ->placeholder('Seleccionar un empleado') // Placeholder
                                    ->helperText('Selecciona el empleado responsable.') // Ayuda para el campo de empleado

                                    // Botón para ver información del empleado
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('view_employee')
                                            ->icon('heroicon-o-eye')
                                            ->tooltip('Ver información del supervisor')
                                            ->color('info')
                                            ->action(function (callable $get) {
                                                $employeeId = $get('employee_id');
                                                if (!$employeeId) {
                                                    Notification::make()
                                                        ->title('Selecciona un supervisor primero')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            })
                                            ->modalContent(function (callable $get) {
                                                $employeeId = $get('employee_id');
                                                if (!$employeeId) return null;

                                                $employee = Employee::with('user')->find($employeeId);
                                                if (!$employee) return null;

                                                return view('filament.components.employee-info-modal', compact('employee'));
                                            })
                                            ->modalHeading('Información del Supervisor')
                                            ->modalSubmitAction(false)
                                            ->modalCancelActionLabel('Cerrar')
                                            ->modalWidth('2xl')
                                            ->visible(fn(callable $get) => !empty($get('employee_id')))
                                    )
                                    ->afterStateHydrated(function (callable $get, callable $set) {
                                        $employeeId = $get('employee_id');
                                        if ($employeeId) {
                                            $employee = Employee::with('user')->find($employeeId);
                                            if ($employee) {
                                                $set('document_type', $employee->document_type);
                                                $set('document_number', $employee->document_number);
                                                $set('address', $employee->address);
                                                $set('date_contract', $employee->date_contract);
                                                $set('user_email', $employee->user?->email);
                                                $set('user_is_active', $employee->user?->is_active ? 'Activo' : 'Inactivo');
                                            } else {
                                                $set('user_email', null);
                                                $set('user_is_active', null);
                                            }
                                        }
                                    }),

                                // FIN DE SELECT DE EMPLEADO

                                // INICIO DE INPUT DE NOMBRE DEL REPORTE
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nombre del reporte'),
                                // FIN DE INPUT DE NOMBRE DEL REPORTE

                                // INICIO DE INPUT DE FECHA
                                Forms\Components\DatePicker::make('report_date')
                                    ->label('Fecha')
                                    ->native(false) // Desactiva el selector nativo para usar el de Filament
                                    ->default(now())
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->helperText('Selecciona la fecha y hora del trabajo'),
                                // FIN DE INPUT DE FECHA

                                // INICIO DE INPUT DE HORA DE INICIO
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('Hora de inicio')
                                    ->default(now()->format('H:i'))
                                    ->seconds(false)
                                    ->displayFormat(format: 'H:i')
                                    ->helperText('Selecciona la hora de inicio del trabajo'),
                                // FIN DE INPUT DE HORA DE INICIO

                                // INICIO DE INPUT DE HORA DE FINALIZACIÓN
                                Forms\Components\TimePicker::make('end_time')
                                    ->label('Hora de finalización')
                                    ->default(now()->format('H:i'))
                                    ->seconds(false)
                                    ->displayFormat(format: 'H:i')
                                    ->helperText('Selecciona la hora de finalización del trabajo')
                                    // Usamos afterStateUpdated para validar y limpiar el campo
                                    ->afterStateUpdated(function ($state, $get, $livewire) {
                                        $startTime = $get('start_time');
                                        $endTime = $state;

                                        // Si no hay hora de inicio, no validamos
                                        if (!$startTime || !$endTime) {
                                            return;
                                        }

                                        $startCarbon = \Carbon\Carbon::parse($startTime);
                                        $endCarbon = \Carbon\Carbon::parse($endTime);

                                        if ($endCarbon->lessThan($startCarbon)) {
                                            // Envía una notificación de error
                                            Notification::make()
                                                ->title('Error de validación')
                                                ->body('La hora de finalización no puede ser anterior a la hora de inicio.')
                                                ->danger()
                                                ->duration(5000)
                                                ->send();

                                            // Limpiamos el campo 'end_time'
                                            $livewire->form->fill(['end_time' => null]);
                                        }
                                    }),
                                // FIN DE INPUT DE HORA DE FINALIZACIÓN

                            ]),

                        // FIN DE TAB DE INFORMACIÓN GENERAL

                        // INICIO TAB DESCRIPCIÓN DEL REPORTE
                        Tab::make('Descripción')
                            ->icon('heroicon-o-clipboard-document-check')
                            ->columns(2)
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->label('Descripción del reporte')
                                    ->required()
                                    ->helperText('Proporciona una descripción detallada del trabajo realizado.')
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                                Forms\Components\RichEditor::make('suggestions')
                                    ->label('Sugerencias')
                                    ->helperText('Proporciona sugerencias o comentarios adicionales sobre el trabajo realizado.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),
                        // FIN TAB DESCRIPCIÓN DEL REPORTE

                        // INICIO DEL TAB DE HERRAMIENTAS Y MATERIALES
                        Tab::make('Herramientas y materiales')
                            ->icon('heroicon-o-wrench')
                            ->columns(2)
                            ->schema([
                                Forms\Components\RichEditor::make('tools')
                                    ->label('Herramientas')
                                    ->helperText('Detalla las herramientas utilizadas durante el trabajo.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                                Forms\Components\RichEditor::make('materials')
                                    ->label('Materiales')
                                    ->helperText('Detalla los materiales utilizados durante el trabajo.')
                                    ->maxLength(5000)
                                    ->toolbarButtons([
                                        'attachFiles',
                                        'bold',
                                        'bulletList',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'orderedList',
                                        'redo',
                                        'strike',
                                        'underline',
                                        'undo',
                                    ]),
                            ]),
                        // FIN DEL TAB DE HERRAMIENTAS Y MATERIALES

                        // INICIO DE TAB DE FIRMAS
                        Tab::make('Firmas')
                            ->icon('heroicon-o-pencil-square')
                            ->columns(2)
                            ->schema([
                                SignaturePad::make('manager_signature')
                                    ->label('Firma del gerente / subgerente')
                                    ->dotSize(2.0)
                                    ->penColor('#000')  // Color negro en modo claro
                                    ->penColorOnDark('#00f')  // Color azul en modo oscuro para mayor visibilidad
                                    ->lineMinWidth(0.2)
                                    ->lineMaxWidth(2.5)
                                    ->throttle(16)
                                    ->minDistance(5)
                                    ->velocityFilterWeight(0.7)
                                    ->confirmable(),
                                SignaturePad::make('employee_signature')
                                    ->label('Firma del Validado por supervisor / técnico')
                                    ->dotSize(2.0)
                                    ->penColor('#000')  // Color negro en modo claro
                                    ->penColorOnDark('#00f')  // Color azul en modo oscuro para mayor visibilidad
                                    ->lineMinWidth(0.2)
                                    ->lineMaxWidth(2.5)
                                    ->throttle(16)
                                    ->minDistance(5)
                                    ->velocityFilterWeight(0.7)
                                    ->confirmable(),
                            ]),
                        // FIN DE TAB DE FIRMAS
                    ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre de visita')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.full_name')
                    ->label('Encargado de visita')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado el')
                    ->dateTime()
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado el')
                    ->dateTime()
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Hora de inicio')
                    ->dateTime()
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Hora de fin')
                    ->date('d/m/Y')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('report_date')
                    ->label('Fecha de reporte')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_report')
                    ->label('Generar PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document')
                    ->url(fn($action) => route('visit-report.pdf', $action->getRecord()->id))
                    ->openUrlInNewTab()
                    ->visible(fn($action) => $action->getRecord()->visitPhotos()->count() > 0)
                    ->tooltip('Generar reporte PDF del trabajo realizado'),

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
            //
            VisitPhotosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'view' => Pages\ViewVisit::route('/{record}'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }
}
