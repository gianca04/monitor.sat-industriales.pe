<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRequest extends CreateRecord
{
    protected static string $resource = RequestResource::class;
    protected static ?string $title = 'Crear Solicitud de Trabajo';

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Solicitud de Trabajo Creada Exitosamente';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
