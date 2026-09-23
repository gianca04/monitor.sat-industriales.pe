<?php

namespace App\Filament\Resources\RequirementResource\Pages;

use App\Filament\Resources\RequirementResource;
use App\Models\Requirement;
use Filament\Resources\Pages\Page;

class CreateRequirement extends Page
{
    protected static string $resource = RequirementResource::class;

    protected static string $view = 'filament.resources.requirement-resource.pages.manage-requirement';

    public ?Requirement $record = null;

    public function getTitle(): string
    {
        return 'Crear Requerimiento';
    }
}
