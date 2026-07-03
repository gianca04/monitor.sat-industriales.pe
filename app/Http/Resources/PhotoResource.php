<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_report_id' => $this->work_report_id,
            'photo_path' => $this->photo_path ? url(Storage::url($this->photo_path)) : null,
            'descripcion' => $this->descripcion ?? '',
            'before_work_photo_path' => $this->before_work_photo_path ? url(Storage::url($this->before_work_photo_path)) : null,
            'before_work_descripcion' => $this->before_work_descripcion ?? '',
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
