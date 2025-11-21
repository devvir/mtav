<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read int $unit_type_id
 * @property-read string $name
 */
class UpdateFamilyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'unit_type_id' => [
                'required', new BelongsToProject(
                    UnitType::class,
                    $this->route('family')->project_id,
                    'validation.unit_type_belongs_to_project'
                ),
            ],
            'name' => 'required|string|between:2,255',
        ];
    }
}
