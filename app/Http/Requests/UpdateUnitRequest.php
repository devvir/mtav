<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $unit_type_id
 * @property-read string $identifier
 */
class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_type_id' => [
                'required',
                new BelongsToProject(
                    UnitType::class,
                    $this->route('unit')->project_id,
                    'validation.unit_type_belongs_to_project'
                ),
            ],
            'identifier' => 'required|string|max:255',
        ];
    }
}
