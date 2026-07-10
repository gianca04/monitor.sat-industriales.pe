<?php

namespace App\Filament\Resources\DeliveryDetailResource\Pages;

use App\Filament\Resources\DeliveryDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Pages\ListRecords\Tab;

class ListDeliveryDetails extends ListRecords
{
    protected static string $resource = DeliveryDetailResource::class;

    protected static string $view = 'filament.pages.list-delivery-details';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(fn (): string => \App\Filament\Resources\DeliveryResource::getUrl('create')),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos los Requerimientos'),
            'low_stock' => Tab::make('EPPs por Reponer'),
        ];
    }
}
