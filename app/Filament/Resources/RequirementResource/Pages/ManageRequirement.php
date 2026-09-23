<?php

namespace App\Filament\Resources\RequirementResource\Pages;

use App\Filament\Resources\RequirementResource;
use App\Models\Requirement;
use Filament\Resources\Pages\Page;

class ManageRequirement extends Page
{
    protected static string $resource = RequirementResource::class;

    protected static string $view = 'filament.resources.requirement-resource.pages.manage-requirement';

    public ?Requirement $record = null;

    public function mount(int|string|\App\Models\Requirement|null $record = null): void
    {
        if ($record) {
            $id = $record instanceof \App\Models\Requirement ? $record->id : $record;
            $this->record = \App\Models\Requirement::with([
                'subClient.client',
                'requirementLists.item.unit',
                'requirementLists.item.subcategory.category',
            ])->findOrFail($id);
        }
    }

    public function getTitle(): string
    {
        return $this->record ? 'Editar Requerimiento #'.$this->record->id : 'Crear Requerimiento';
    }
}
