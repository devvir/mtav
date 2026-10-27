<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FamilyRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');
        /** @var Family|null $family */
        $family = $this->route('family');

        return [
            'name' => [
                'required', 'string', 'between:2,120',
                Rule::unique('families', 'name')->where('project_id', $project->id)->ignore($family),
            ],
            'unit_type_id' => ['required', Rule::exists(UnitType::class, 'id')->where('project_id', $project->id)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Project $project */
            $project = $this->route('project');
            $max = $project->limit('families');

            if (! $this->route('family') && $max !== null && $project->families()->count() >= $max) {
                $validator->errors()->add('name', __('validation.limit_families', ['max' => $max]));
            }
        }];
    }
}
