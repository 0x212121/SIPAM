<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $projectId = $this->route('project')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('projects', 'code')->ignore($projectId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'auditee_agency' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'status' => ['sometimes', Rule::in(['draft', 'review', 'final'])],
        ];
    }
}
