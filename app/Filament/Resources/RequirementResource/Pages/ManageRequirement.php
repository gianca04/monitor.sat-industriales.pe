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

    public function mount(int|string|null $record = null): void
    {
        if ($record) {
            $this->record = Requirement::findOrFail($record);
        }
    }

    public function getTitle(): string
    {
        return $this->record ? 'Editar Requerimiento #'.$this->record->id : 'Crear Requerimiento';
    }
}
