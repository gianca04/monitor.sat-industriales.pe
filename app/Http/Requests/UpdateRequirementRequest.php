<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sub_client_id' => ['sometimes', 'required', 'integer', 'exists:sub_clients,id'],
            'activity_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
