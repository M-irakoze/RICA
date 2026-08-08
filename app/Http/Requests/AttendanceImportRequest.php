<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_file' => ['required', 'file', 'extensions:xlsx,xls,csv'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_file.required' => 'Please upload the attendance file from the facial recognition machine.',
            'attendance_file.extensions' => 'The uploaded file must be an XLS, XLSX, or CSV file.',
        ];
    }
}
