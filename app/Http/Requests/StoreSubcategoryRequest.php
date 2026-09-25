<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubcategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\.\,\-\/\(\)]+$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Debe seleccionar una categoría para la subcategoría.',
            'category_id.integer' => 'El identificador de la categoría debe ser un número entero.',
            'category_id.exists' => 'La categoría seleccionada no existe en el sistema.',
            'name.required' => 'El nombre de la subcategoría es obligatorio.',
            'name.string' => 'El nombre de la subcategoría debe ser texto válido.',
            'name.max' => 'El nombre de la subcategoría no debe exceder los 100 caracteres.',
            'name.regex' => 'El nombre de la subcategoría debe contener texto en español y no se permiten símbolos extraños ni únicamente números.',
        ];
    }
}
