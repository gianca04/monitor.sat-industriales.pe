<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalizar la estructura de los ítems para soportar tanto 'id' como 'item_id'.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->items)) {
            $normalizedItems = [];
            foreach ($this->items as $item) {
                if (is_array($item)) {
                    $normalizedItems[] = [
                        'item_id' => $item['item_id'] ?? $item['id'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                    ];
                }
            }
            $this->merge(['items' => $normalizedItems]);
        }
    }

    public function rules(): array
    {
        return [
            'sub_client_id' => ['sometimes', 'required', 'integer', 'exists:sub_clients,id'],
            'activity_name' => ['nullable', 'string', 'max:255'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required_with:items', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'sub_client_id.required' => 'Debe seleccionar una tienda o sede (subcliente).',
            'sub_client_id.exists' => 'La tienda o sede seleccionada no es válida.',
            'items.*.item_id.required_with' => 'Cada material debe contener un identificador de ítem válido.',
            'items.*.item_id.exists' => 'Uno o más materiales seleccionados no existen en el catálogo.',
            'items.*.quantity.min' => 'La cantidad mínima por ítem debe ser al menos 0.01.',
        ];
    }
}
