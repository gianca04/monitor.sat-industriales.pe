<?php

namespace App\Filament\Resources\DeliveryDetailResource\Pages;

use App\Filament\Resources\DeliveryDetailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryDetail extends EditRecord
{
    protected static string $resource = DeliveryDetailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
