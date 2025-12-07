<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $project_id
 * @property-read int $unit_type_id
 * @property-read string $identifier
 */
class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'project_id'   => 'required|exists:projects,id',
            'unit_type_id' => [
                'required',
                new BelongsToProject(
                    UnitType::class,
                    $this->route('unit')->project_id,
                    'validation.unit_type_belongs_to_project'
                ),
            ],
            'identifier' => 'required|string|between:2,255',
        ];
    }
}
