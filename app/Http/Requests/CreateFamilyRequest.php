<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read string $name
 * @property-read int $project_id
 * @property-read int $unit_type_id
 */
class CreateFamilyRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|between:2,255',
            'project_id'   => 'required|exists:projects,id',
            'unit_type_id' => [
                'required', new BelongsToProject(
                    UnitType::class,
                    $this->project_id,
                    'validation.unit_type_belongs_to_project'
                ),
            ],
        ];
    }
}
