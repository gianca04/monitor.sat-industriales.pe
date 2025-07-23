<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Filament\Resources\ProjectResource\RelationManagers\TimesheetsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\WorkReportsRelationManager;
use App\Models\Project;
use App\Models\Quote;
use App\Models\WorkReport;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{

    use Translatable;

    protected static ?string $pluralModelLabel = 'Proyectos';
    protected static ?string $modelLabel = 'Proyecto';

    protected static ?string $model = Project::class;

    protected static ?string $navigationGroup = 'Control de operaciones';
    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección: Información básica del proyecto
                Forms\Components\Section::make('Información básica del proyecto')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del proyecto')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('quote_id')
                            ->label('Cotización')
                            ->required()
                            ->searchable()
                            ->prefixIcon('heroicon-m-calculator')
                            ->options(function (callable $get) {
                                $search = $get('search');
                                $sessionQuoteId = session('quote_id');
                                $query = Quote::query()
                                    ->select('quotes.id', 'quotes.correlative', 'quotes.project_description', 'sub_clients.name as sub_client_name', 'clients.business_name as client_name')
                                    ->leftJoin('sub_clients', 'quotes.sub_client_id', '=', 'sub_clients.id')
                                    ->leftJoin('clients', 'quotes.client_id', '=', 'clients.id')
                                    ->when($search, function ($query) use ($search) {
                                        $query->where('quotes.correlative', 'like', "%{$search}%")
                                            ->orWhere('quotes.project_description', 'like', "%{$search}%")
                                            ->orWhere('sub_clients.name', 'like', "%{$search}%")
                                            ->orWhere('clients.business_name', 'like', "%{$search}%");
                                    })
                                    ->limit(30);

                                // Si hay un quote_id en sesión y no está en los resultados, inclúyelo
                                if ($sessionQuoteId) {
                                    $query->orWhere('quotes.id', $sessionQuoteId);
                                }

                                return $query->get()
                                    ->unique('id')
                                    ->mapWithKeys(function ($quote) {
                                        $label = "{$quote->correlative} - {$quote->project_description} ({$quote->sub_client_name} / {$quote->client_name})";
                                        return [$quote->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->default(fn() => session('quote_id'))
                            ->required(),


                        // ...existing code...
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de inicio')
                            ->default(now())
                            ->required()
                            ->maxDate(fn(callable $get) => $get('end_date')), // Valida contra end_date

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de finalización')
                            ->default(now()->addDays(30))
                            ->required()
                            ->minDate(fn(callable $get) => $get('start_date')), // Valida contra start_date
                        // ...existing code...

                        Forms\Components\Placeholder::make('status_text')
                            ->label('Estado del proyecto')
                            ->content(fn($record) => $record?->status_text ?? 'Sin definir'),

                    ]),

                // Sección: Coordenadas geográficas
                Forms\Components\Section::make('Coordenadas geográficas')
                    ->columns(1)
                    ->collapsed()
                    ->schema([
                        \App\Forms\Components\ubicacion::make('location')
                            ->label('Ubicación en el mapa'),

                    ]),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Proyecto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quote.client.business_name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('quote.sub_client.name')
                    ->label('Subcliente')
                    ->searchable()
                    ->sortable(),




                Tables\Columns\BadgeColumn::make('status_text')
                    ->label('Estado del proyecto')
                    ->formatStateUsing(fn($state, $record) => $record?->status_text ?? 'Sin definir')
                    ->colors([
                        'gray' => fn($state) => $state === 'Sin definir',
                        'primary' => fn($state) => $state === 'No iniciado',
                        'warning' => fn($state) => $state === 'En proceso',
                        'success' => fn($state) => $state === 'Culminado',
                    ]),


                Tables\Columns\TextColumn::make('quote.correlative')
                    ->label('Correlativo de Cotización')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($record) => $record->quote ? "{$record->quote->correlative} - {$record->quote->project_description}" : 'Sin cotización'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('location_address')
                    ->label('Ubicación')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->location_address ?? 'Sin ubicación';
                    }),

                Tables\Columns\TextColumn::make('coordinates')
                    ->label('Coordenadas')
                    ->formatStateUsing(function ($record) {
                        return $record->coordinates ?? 'Sin coordenadas';
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([

                Tables\Filters\SelectFilter::make('status_text')
                    ->label('Estado del proyecto')
                    ->native(false)
                    ->options([
                        'No iniciado' => 'No iniciado',
                        'En proceso' => 'En proceso',
                        'Culminado' => 'Culminado',
                        'Sin definir' => 'Sin definir',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!isset($data['value'])) {
                            return $query;
                        }
                        return $query->whereRaw("(
                            CASE
                                WHEN start_date IS NOT NULL AND ? < DATE(start_date) THEN 'No iniciado'
                                WHEN end_date IS NOT NULL AND ? > DATE(end_date) THEN 'Culminado'
                                WHEN start_date IS NOT NULL AND end_date IS NOT NULL AND ? >= DATE(start_date) AND ? <= DATE(end_date) THEN 'En proceso'
                                ELSE 'Sin definir'
                            END
                        ) = ?", [now()->toDateString(), now()->toDateString(), now()->toDateString(), now()->toDateString(), $data['value']]);
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            TimesheetsRelationManager::class,
            WorkReportsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
