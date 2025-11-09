<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $unit_type_id
 * @property-read string $identifier
 */
class CreateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_type_id' => ['required', new BelongsToProject(
                UnitType::class,
                currentProjectId(),
                'validation.unit_type_belongs_to_project'
            )],
            'identifier' => 'required|string|max:255',
        ];
    }
}
