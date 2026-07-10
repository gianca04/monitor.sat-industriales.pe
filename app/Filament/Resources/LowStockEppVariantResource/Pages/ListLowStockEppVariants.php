<?php

namespace App\Filament\Resources\LowStockEppVariantResource\Pages;

use App\Filament\Resources\LowStockEppVariantResource;
use Filament\Resources\Pages\ListRecords;

class ListLowStockEppVariants extends ListRecords
{
    protected static string $resource = LowStockEppVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
