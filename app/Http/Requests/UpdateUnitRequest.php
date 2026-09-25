<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $unitId = $this->route('unit')?->id ?? $this->route('unit');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('units', 'name')->ignore($unitId),
                'regex:/[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ]/u',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s\.\-\(\)\/]+$/u',
            ],
            'symbol' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\/\.\-\%²³]+$/u',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la unidad de medida es obligatorio.',
            'name.string' => 'El nombre de la unidad debe ser una cadena de texto válida.',
            'name.max' => 'El nombre de la unidad no debe superar los 255 caracteres.',
            'name.unique' => 'Esta unidad de medida ya se encuentra registrada.',
            'name.regex' => 'El nombre de la unidad debe contener texto en español y no puede componerse únicamente de números ni contener caracteres no permitidos.',
            'symbol.required' => 'El símbolo de la unidad de medida es obligatorio.',
            'symbol.string' => 'El símbolo debe ser una cadena de texto válida.',
            'symbol.max' => 'El símbolo no debe superar los 50 caracteres.',
            'symbol.regex' => 'El símbolo contiene caracteres no válidos.',
        ];
    }
}
