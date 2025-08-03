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
            Actions\Action::make('generate_report')
                ->label('Generar PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->icon('heroicon-o-document')
                ->url(fn() => route('work-report.pdf', $this->record->id))
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->photos()->count() > 0)
                ->tooltip('Generar reporte PDF del trabajo realizado'),
            Actions\Action::make('generate_word_report')
                ->label('Generar Word')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn() => route('work-report.word', $this->record->id))
                ->openUrlInNewTab()
                ->visible(fn() => $this->record->photos()->count() > 0)
                ->tooltip('Generar reporte Word del trabajo realizado'),
        ];
    }
}
