<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_client_id' => ['required', 'integer', 'exists:sub_clients,id'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required_with:items', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }
}
