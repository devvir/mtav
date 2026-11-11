<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A unit, of an existing type or of a new one created on the way (when adding it: a unit
 * changed moves to a type that exists).
 */
class UnitRequest extends FormRequest
{
    /** How many corners first, on its own: each corner's rules run only on a shape of a size */
    protected function prepareForValidation(): void
    {
        validator($this->all(), ['polygon' => ['nullable', 'list', 'min:3', 'max:64']])->validate();
    }

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
                $unit ? 'required' : 'required_without:new_unit_type', 'nullable',
                Rule::exists(UnitType::class, 'id')->where('project_id', $project->id),
            ],
            // On an update the form still sends it, empty: only a type given is refused
            'new_unit_type'      => $unit ? ['prohibited'] : ['nullable', 'array'],
            'new_unit_type.name' => [
                'required_with:new_unit_type', 'string', 'between:2,80',
                Rule::unique('unit_types', 'name')->where('project_id', $project->id),
            ],
            'new_unit_type.description' => ['nullable', 'string', 'max:500'],
            // Where it was drawn, when added on the plan
            'polygon'     => ['nullable', 'list', 'min:3', 'max:64'],
            'polygon.*'   => ['list', 'size:2'],
            'polygon.*.*' => ['numeric'],
            'floor'       => ['nullable', 'integer', 'between:-5,100'],
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
        },
        ];
    }
}
