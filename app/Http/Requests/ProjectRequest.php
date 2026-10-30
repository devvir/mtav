<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');

        return [
            'name'         => ['required', 'string', 'between:2,120', Rule::unique('projects', 'name')->ignore($project)],
            'description'  => ['required', 'string', 'between:2,2000'],
            'organization' => ['nullable', 'string', 'between:2,120'],
            'listed'       => ['sometimes', 'boolean'],
            'open_to_join' => ['sometimes', 'boolean'],
        ];
    }
}
