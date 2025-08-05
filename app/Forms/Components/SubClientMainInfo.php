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
                    TextInput::make('address')
                        ->label('Ubicación')
                        ->placeholder('Dirección de la sede')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-map-pin'),
                ])
                ->columns(1),
        ];
    }
}
