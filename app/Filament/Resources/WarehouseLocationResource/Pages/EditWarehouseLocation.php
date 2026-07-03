<?php

namespace App\Filament\Resources\WarehouseLocationResource\Pages;

use App\Filament\Resources\WarehouseLocationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWarehouseLocation extends EditRecord
{
    protected static string $resource = WarehouseLocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
