<?php

namespace App\Filament\Resources\DeliveryDetailResource\Pages;

use App\Filament\Resources\DeliveryDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryDetails extends ListRecords
{
    protected static string $resource = DeliveryDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => \App\Filament\Resources\DeliveryResource::getUrl('create')),
        ];
    }
}
