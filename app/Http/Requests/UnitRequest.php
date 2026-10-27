<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A unit, of an existing type or of a new one created on the way.
 */
class UnitRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var Unit|null $unit */
        $unit = $this->route('unit');

        return [
            'identifier' => [
                'required', 'string', 'between:1,80',
                Rule::unique('units', 'identifier')->where('project_id', $project->id)->ignore($unit),
            ],
            'unit_type_id' => [
                'required_without:new_unit_type', 'nullable',
                Rule::exists(UnitType::class, 'id')->where('project_id', $project->id),
            ],
            'new_unit_type'      => ['nullable', 'array'],
            'new_unit_type.name' => [
                'required_with:new_unit_type', 'string', 'between:2,80',
                Rule::unique('unit_types', 'name')->where('project_id', $project->id),
            ],
            'new_unit_type.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Project $project */
            $project = $this->route('project');
            $max = $project->limit('units');

            if (! $this->route('unit') && $max !== null && $project->units()->count() >= $max) {
                $validator->errors()->add('identifier', __('validation.limit_units', ['max' => $max]));
            }
        }];
    }
}
