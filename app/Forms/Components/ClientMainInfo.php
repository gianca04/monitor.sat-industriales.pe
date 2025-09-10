<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

class ClientMainInfo
{
    public static function make(): Split
    {
        return Split::make([
            Section::make('Información principal')
                ->description('Datos generales del cliente')
                ->icon('heroicon-o-identification')
                ->schema([
                    TextInput::make('business_name')
                        ->label('Razón social')
                        ->placeholder('Nombre de la empresa o persona')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-building-office-2'),
                    Select::make('document_type')
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
                        ->alphaNum()
                        ->prefixIcon('heroicon-o-identification'),
                    TextInput::make('document_number')
                        ->label('Número de documento')
                        ->placeholder('Ej: 12345678901')
                        ->required()
                        ->maxLength(11)
                        ->minLength(8)
                        ->alphaNum()
                        ->prefixIcon('heroicon-o-hashtag'),
                    Select::make('person_type')
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
                    Textarea::make('description')
                        ->label('Descripción')
                        ->placeholder('Descripción del cliente')
                        ->columnSpanFull()
                        ->rows(2)
                        ->autosize(),
                ])
                ->columns([
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 2,
                    '2xl' => 2,
                ]),

            Section::make('Contacto')
                ->icon('heroicon-o-phone')
                ->columns([
                    'sm' => 1,
                    'md' => 1,
                    'xl' => 2,
                    '2xl' => 2,
                ])
                ->description('Información de contacto y dirección')
                ->schema([
                    TextInput::make('address')
                        ->label('Dirección')
                        ->placeholder('Dirección fiscal o comercial')
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-map-pin'),
                    TextInput::make('contact_phone')
                        ->label('Teléfono de contacto')
                        ->placeholder('Ej: +51 999 999 999')
                        ->tel()
                        ->maxLength(9)
                        ->minLength(7)
                        ->prefixIcon('heroicon-o-phone'),
                    TextInput::make('contact_email')
                        ->label('Correo electrónico')
                        ->placeholder('correo@ejemplo.com')
                        ->email()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-envelope'),
                    FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->imageEditor()
                        ->directory('logos')
                        ->columnSpanFull()
                        ->hint('Sube el logo de la empresa')
                        ->panelLayout('integrated')
                        ->directory('uploads/users')
                        ->previewable(true),
                ])
                ->columns(2),
        ])
        ->from('md')
        ->columnSpanFull();
    }
}