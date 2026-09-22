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
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('units', 'name')->ignore($unitId)],
            'symbol' => ['sometimes', 'required', 'string', 'max:50'],
        ];
    }
}
