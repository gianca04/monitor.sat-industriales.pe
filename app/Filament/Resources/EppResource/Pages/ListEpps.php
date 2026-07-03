<?php

namespace App\Filament\Resources\EppResource\Pages;

use App\Filament\Resources\EppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEpps extends ListRecords
{
    protected static string $resource = EppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
