<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read string $name
 * @property-read int $project_id
 * @property-read int|null $unit_type_id
 */
class CreateFamilyRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        $projectId = $this['project_id'];

        return [
            'name' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:projects,id',
            'unit_type_id' => array_filter([
                'nullable',
                'integer',
                $projectId ? new BelongsToProject(UnitType::class, $projectId, 'validation.unit_type_belongs_to_project') : null,
            ]),
        ];
    }
}