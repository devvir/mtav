<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Validation\Rule;

/**
 * @property-read string|null $name
 * @property-read string|null $description
 * @property-read string|null $organization
 */
class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'between:2,255',
                Rule::unique(Project::class)->ignore($this->route('project')),
            ],
            'description'  => 'required|string|between:2,500',
            'organization' => 'required|string|between:2,255',
        ];
    }
}
