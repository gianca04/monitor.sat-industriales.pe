<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;

class ProjectMainInfo
{
    public static function make(): Section
    {
        return Section::make('Información básica del proyecto')
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->label('Nombre del proyecto')
                    ->required()
                    ->maxLength(255),
                Select::make('quote_id')
                    ->label('Cotización')
                    ->searchable()
                    ->prefixIcon('heroicon-m-calculator')
                    ->options(function (callable $get) {
                        $search = $get('search');
                        $sessionQuoteId = session('quote_id');
                        $query = \App\Models\Quote::query()
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
                    ->default(fn() => session('quote_id')),
                DatePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->default(now())
                    ->required()
                    ->maxDate(fn(callable $get) => $get('end_date')),
                DatePicker::make('end_date')
                    ->label('Fecha de finalización')
                    ->default(now()->addDays(30))
                    ->required()
                    ->minDate(fn(callable $get) => $get('start_date')),
                Placeholder::make('status_text')
                    ->label('Estado del proyecto:')
                    ->extraAttributes(['class' => 'text-2xl font-bold text-primary-600'])
                    ->content(fn($record) => $record?->status_text ?? 'Sin definir'),
            ]);
    }
}
