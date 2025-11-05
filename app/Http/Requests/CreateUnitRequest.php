<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read string $number
 * @property-read int $project_id
 * @property-read int $unit_type_id
 * @property-read int|null $family_id
 */
class CreateUnitRequest extends ProjectScopedRequest
{
    public function rules(): array
    {
        $projectId = $this['project_id'];

        return [
            'number' => 'required|string|max:255',
            'project_id' => 'required|integer|exists:projects,id',
            'unit_type_id' => array_filter([
                'required',
                'integer',
                $projectId ? new BelongsToProject(UnitType::class, $projectId, 'validation.unit_type_belongs_to_project') : null,
            ]),
            'family_id' => array_filter([
                'nullable',
                'integer',
                $projectId ? new BelongsToProject(Family::class, $projectId, 'validation.family_belongs_to_project') : null,
            ]),
        ];
    }
}
