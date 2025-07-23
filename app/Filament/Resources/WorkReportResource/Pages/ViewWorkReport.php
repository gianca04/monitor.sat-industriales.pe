<?php

namespace App\Filament\Resources\WorkReportResource\Pages;

use App\Filament\Resources\WorkReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkReport extends ViewRecord
{
    protected static string $resource = WorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
