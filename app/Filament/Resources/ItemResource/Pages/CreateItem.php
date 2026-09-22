<?php

namespace App\Filament\Resources\ItemResource\Pages;

use App\Actions\GenerateItemSkuAction;
use App\Filament\Resources\ItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateItem extends CreateRecord
{
    protected static string $resource = ItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (empty($data['sku'])) {
            $subcategoryId = $data['subcategory_id'] ?? 0;
            $data['sku'] = app(GenerateItemSkuAction::class)->execute($subcategoryId);
        }

        return $data;
    }
}
