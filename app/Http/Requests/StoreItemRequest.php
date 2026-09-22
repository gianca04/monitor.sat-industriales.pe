<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:255', 'unique:items,sku'],
            'name' => ['required', 'string', 'max:255'],
            'subcategory_id' => ['required', 'integer', 'exists:subcategories,id'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'photo' => ['nullable', 'image', 'max:10240'],
        ];
    }
}
