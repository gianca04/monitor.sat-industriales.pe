<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'employee_id' => 'required|integer|exists:employees,id',
            'name' => 'required|string|max:255',
            'report_date' => 'date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'tools' => 'nullable|string',
            'personnel' => 'nullable|string',
            'materials' => 'nullable|string',
            'suggestions' => 'nullable|string',
            'supervisor_signature' => 'nullable|string', // Base64 string
            'manager_signature' => 'nullable|string',
        ];
    }
}
