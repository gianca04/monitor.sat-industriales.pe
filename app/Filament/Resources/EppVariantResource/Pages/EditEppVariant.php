<?php

namespace App\Filament\Resources\EppVariantResource\Pages;

use App\Filament\Resources\EppVariantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEppVariant extends EditRecord
{
    protected static string $resource = EppVariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
