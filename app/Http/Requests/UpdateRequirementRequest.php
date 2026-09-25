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
            'activity_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\.\,\-\/\(\)\:\;\#]+$/u',
            ],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.item_id' => ['required_with:items', 'integer', 'exists:items,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'sub_client_id.required' => 'Debe seleccionar una tienda o sede (subcliente).',
            'sub_client_id.integer' => 'El identificador de la tienda o sede debe ser un número entero.',
            'sub_client_id.exists' => 'La tienda o sede seleccionada no es válida.',
            'activity_name.required' => 'El nombre de la actividad es obligatorio.',
            'activity_name.string' => 'El nombre de la actividad debe ser texto válido.',
            'activity_name.max' => 'El nombre de la actividad no debe superar los 255 caracteres.',
            'activity_name.regex' => 'El nombre de la actividad debe contener texto explicativo en español y no puede componerse solo de números o símbolos extraños.',
            'items.array' => 'La lista de materiales debe ser un conjunto válido de elementos.',
            'items.min' => 'Debe incluir al menos un material en la lista del requerimiento.',
            'items.*.item_id.required_with' => 'Cada material debe contener un identificador de ítem válido.',
            'items.*.item_id.integer' => 'El identificador del material debe ser un número entero.',
            'items.*.item_id.exists' => 'Uno o más materiales seleccionados no existen en el catálogo.',
            'items.*.quantity.required_with' => 'Debe especificar la cantidad para cada material.',
            'items.*.quantity.numeric' => 'La cantidad de cada material debe ser un número válido.',
            'items.*.quantity.min' => 'La cantidad mínima por ítem debe ser al menos 0.01.',
        ];
    }
}
