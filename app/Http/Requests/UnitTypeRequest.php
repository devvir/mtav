<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitTypeRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var UnitType|null $unitType */
        $unitType = $this->route('unitType');

        return [
            'name' => [
                'required', 'string', 'between:2,80',
                Rule::unique('unit_types', 'name')->where('project_id', $project->id)->ignore($unitType),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            // The size its units start at on the plan: both, or neither
            'plan_width'  => ['nullable', 'required_with:plan_height', 'numeric', 'between:2,2000'],
            'plan_height' => ['nullable', 'required_with:plan_width', 'numeric', 'between:2,2000'],
        ];
    }
}
