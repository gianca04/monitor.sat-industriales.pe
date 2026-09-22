<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('item')?->id ?? $this->route('item');

        return [
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('items', 'sku')->ignore($itemId)],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'subcategory_id' => ['sometimes', 'required', 'integer', 'exists:subcategories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
