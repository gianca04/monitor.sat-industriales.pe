<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequirementResource extends JsonResource
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
            'sub_client_id' => $this->sub_client_id,
            'sub_client' => $this->whenLoaded('subClient', fn () => [
                'id' => $this->subClient->id,
                'name' => $this->subClient->name,
                'client_id' => $this->subClient->client_id,
            ]),
            'activity_name' => $this->activity_name,
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
                'email' => $this->creator->email,
            ]),
            'items' => RequirementListResource::collection($this->whenLoaded('requirementLists')),
            'items_count' => $this->whenCounted('requirementLists'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
