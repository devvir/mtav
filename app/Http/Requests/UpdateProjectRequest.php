<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $name
 * @property-read string|null $description
 * @property-read string|null $organization
 *
 */
class UpdateProjectRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Project::class, 'name')->ignore($this->route('project')->id)
            ],
            'description' => ['required', 'string', 'max:65535'],
            'organization' => ['required', 'string', 'max:255'],
        ];
    }
}
