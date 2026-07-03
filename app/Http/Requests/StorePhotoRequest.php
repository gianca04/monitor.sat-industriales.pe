<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Cambiar si tienes lógica de roles/permisos
    }

    public function rules(): array
    {
        return [
            'work_report_id' => ['required', 'integer', 'exists:work_reports,id'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            
            // Validaciones para la foto principal
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'], // Max 10MB
            
            // Validaciones para la foto "antes del trabajo"
            'before_work_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'before_work_descripcion' => ['nullable', 'string', 'max:1000'],
        ];
    }
}