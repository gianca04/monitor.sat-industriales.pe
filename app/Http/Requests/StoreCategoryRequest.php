<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:categories,name',
                'regex:/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\.\,\-\/\(\)]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\.\,\-\/\(\)\:\;\r\n]+$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.string' => 'El nombre de la categoría debe ser texto válido.',
            'name.max' => 'El nombre de la categoría no debe exceder los 100 caracteres.',
            'name.unique' => 'Ya existe una categoría con este nombre.',
            'name.regex' => 'El nombre de la categoría debe contener texto en español y no se permiten símbolos extraños ni únicamente números.',
            'description.string' => 'La descripción debe ser texto válido.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
            'description.regex' => 'La descripción contiene caracteres o símbolos no permitidos.',
        ];
    }
}
