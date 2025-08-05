<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class SubClientMainInfo
{
    public static function make(): array
    {
        return [
            Hidden::make('client_id')
                ->default(fn(callable $get) => $get('client_id')),

            Section::make('Información de la Sede')
                ->description('Datos de la nueva sede')
                ->icon('heroicon-o-building-office')
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre de la sede')
                        ->placeholder('Ej: Sede Central, Sucursal Norte')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-building-office-2'),
                    Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Descripción de la sede')
                        ->maxLength(500)
                        ->rows(2)
                        ->autosize(),
                    TextInput::make('location')
                        ->label('Ubicación')
                        ->placeholder('Dirección de la sede')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-map-pin'),
                ])
                ->columns(1),

            Section::make('Coordenadas (Opcional)')
                ->description('Ubicación geográfica de la sede')
                ->icon('heroicon-o-globe-americas')
                ->schema([
                    TextInput::make('latitude')
                        ->label('Latitud')
                        ->placeholder('Ej: -12.046374')
                        ->numeric()
                        ->step(0.000001)
                        ->prefixIcon('heroicon-o-arrow-long-up'),
                    TextInput::make('longitude')
                        ->label('Longitud')
                        ->placeholder('Ej: -77.042793')
                        ->numeric()
                        ->step(0.000001)
                        ->prefixIcon('heroicon-o-arrow-long-right'),
                ])
                ->columns(2),
        ];
    }
}
