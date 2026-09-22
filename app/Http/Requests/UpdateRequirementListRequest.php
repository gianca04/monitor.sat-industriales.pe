<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequirementListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['sometimes', 'required', 'integer', 'exists:items,id'],
            'quantity' => ['sometimes', 'required', 'numeric', 'min:0.01'],
        ];
    }
}
