<?php

namespace App\Filament\Resources\EppResource\Pages;

use App\Filament\Resources\EppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEpp extends EditRecord
{
    protected static string $resource = EppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
