<?php

namespace App\Filament\Resources\EppVariantResource\Pages;

use App\Filament\Resources\EppVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEppVariants extends ListRecords
{
    protected static string $resource = EppVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
