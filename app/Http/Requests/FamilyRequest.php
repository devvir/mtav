<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\UnitType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class FamilyRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Project $project */
        $project = $this->route('project');

        return [
            'name'         => ['required', 'string', 'between:2,120'],
            'unit_type_id' => ['required', Rule::exists(UnitType::class, 'id')->where('project_id', $project->id)],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            /** @var Project $project */
            $project = $this->route('project');

            try {
                $this->route('family') || $project->ensureRoomForFamily('name');
            } catch (ValidationException $full) {
                $validator->errors()->merge($full->errors());
            }
        },
        ];
    }
}
