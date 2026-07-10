<?php

namespace App\Filament\Resources\EppResource\Pages;

use App\Filament\Resources\EppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Pages\ListRecords\Tab;

class ListEpps extends ListRecords
{
    protected static string $resource = EppResource::class;

    protected static string $view = 'filament.pages.list-epps';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos los EPPs'),
            'low_stock' => Tab::make('EPPs por Reponer'),
            'movements' => Tab::make('Movimientos de Stock'),
        ];
    }
}
