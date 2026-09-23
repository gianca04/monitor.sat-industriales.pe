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
}
