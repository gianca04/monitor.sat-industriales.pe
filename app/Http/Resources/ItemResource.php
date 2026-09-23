<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'subcategory_id' => $this->subcategory_id,
            'subcategory' => $this->whenLoaded('subcategory', fn () => [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                'category_id' => $this->subcategory->category_id,
                'category' => $this->subcategory->relationLoaded('category') && $this->subcategory->category ? [
                    'id' => $this->subcategory->category->id,
                    'name' => $this->subcategory->category->name,
                ] : null,
            ]),
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit->id,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
            ]),
            'photo' => $this->photo_url,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
