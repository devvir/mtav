<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $project_id
 * @property-read int $unit_type_id
 * @property-read string $name
 */
class CreateFamilyRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        return [
            'project_id'   => 'required|exists:projects,id',
            'unit_type_id' => ['required', new BelongsToProject(
                UnitType::class,
                $this->project_id,
                'validation.unit_type_belongs_to_project'
            )],
            'name' => 'required|string|between:2,255',
        ];
    }
}
