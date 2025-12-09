<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $project_id
 * @property-read int|null $unit_type_id
 * @property-read string $identifier
 * @property-read string|null $new_unit_type_name
 * @property-read string|null $new_unit_type_description
 */
class CreateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id'   => 'required|exists:projects,id',
            'identifier'   => 'required|string|between:2,255',
            'unit_type_id' => [
                'required_without:new_type_name',
                new BelongsToProject(
                    UnitType::class,
                    currentProjectId(),
                    'validation.unit_type_belongs_to_project'
                ),
            ],
            'new_type_name'        => 'required_without:unit_type_id|nullable|string|between:2,255',
            'new_type_description' => 'required_without:unit_type_id|nullable|string|between:2,255',
        ];
    }
}
