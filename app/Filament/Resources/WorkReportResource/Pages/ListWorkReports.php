<?php

namespace App\Filament\Resources\WorkReportResource\Pages;

use App\Filament\Resources\WorkReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkReports extends ListRecords
{
    protected static string $resource = WorkReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
