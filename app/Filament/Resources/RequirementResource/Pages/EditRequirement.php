<?php

namespace App\Filament\Resources\RequirementResource\Pages;

use App\Filament\Resources\RequirementResource;
use App\Models\Requirement;
use Filament\Resources\Pages\Page;

class EditRequirement extends Page
{
    protected static string $resource = RequirementResource::class;

    protected static string $view = 'filament.resources.requirement-resource.pages.manage-requirement';

    public ?Requirement $record = null;

    public function mount(int|string|Requirement|null $record = null): void
    {
        if ($record) {
            $id = $record instanceof Requirement ? $record->id : $record;
            $this->record = Requirement::with([
                'subClient.client',
                'requirementLists.item.unit',
                'requirementLists.item.subcategory.category',
            ])->findOrFail($id);
        }
    }

    public function getTitle(): string
    {
        return 'Editar Requerimiento #'.$this->record->id;
    }

    protected function getHeaderActions(): array
    {
        if (! $this->record) {
            return [];
        }

        return [
            \Filament\Actions\Action::make('export_async')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    $exportService = app(\App\Services\RequirementExportService::class);
                    $tempPath = $exportService->exportAll($this->record);

                    return response()->download($tempPath, 'Requerimiento_'.$this->record->id.'.xlsx')->deleteFileAfterSend(true);
                }),
        ];
    }

    public function exportSelectedItems(array $listIds)
    {
        if (! $this->record || empty($listIds)) {
            return;
        }

        $items = $this->record->requirementLists()
            ->whereIn('id', $listIds)
            ->with(['item', 'item.unit'])
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $exportService = app(\App\Services\RequirementExportService::class);
        $tempPath = $exportService->export($this->record, $items);

        return response()->download($tempPath, 'Requerimiento_Seleccion_'.$this->record->id.'.xlsx')->deleteFileAfterSend(true);
    }
}
