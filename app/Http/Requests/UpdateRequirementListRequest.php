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

    public function messages(): array
    {
        return [
            'item_id.required' => 'Debe seleccionar un material válido.',
            'item_id.integer' => 'El identificador del material debe ser un número entero.',
            'item_id.exists' => 'El material seleccionado no existe en el catálogo.',
            'quantity.required' => 'La cantidad del material es obligatoria.',
            'quantity.numeric' => 'La cantidad debe ser un valor numérico válido.',
            'quantity.min' => 'La cantidad mínima por material debe ser al menos 0.01.',
        ];
    }
}
