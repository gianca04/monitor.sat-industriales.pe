<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Usamos 'sometimes' para permitir actualizaciones parciales (PATCH) 
            // donde no se envíe este campo si no cambia.
            'work_report_id' => ['sometimes', 'integer', 'exists:work_reports,id'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            
            // CRÍTICO: Aquí quitamos 'required'. Si no envían foto, mantenemos la anterior.
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            
            'before_work_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'before_work_descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}