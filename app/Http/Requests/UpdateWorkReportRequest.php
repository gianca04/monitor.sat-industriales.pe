<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkReportRequest extends FormRequest
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
            'name' => 'nullable|string|max:255',
            'project_id' => 'sometimes|integer|exists:projects,id',
            'employee_id' => 'sometimes|integer|exists:employees,id',
            'report_date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'description' => 'nullable|string',
            'tools' => 'nullable|string',
            'personnel' => 'nullable|string',
            'materials' => 'nullable|string',
            'suggestions' => 'nullable|string',
            'supervisor_signature' => 'nullable|string',
            'manager_signature' => 'nullable|string',
        ];
    }
}
