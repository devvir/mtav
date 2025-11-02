<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\UnitType;
use App\Rules\BelongsToProject;
use App\Http\Requests\FormRequest;

/**
 * @property-read string $name
 * @property-read int $unit_type_id
 *
 */
class UpdateFamilyRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Family $family */
        $family = $this->route('family');
        $projectId = $family->project_id;

        return [
            'name' => 'required|string|max:255',
            'unit_type_id' => array_filter([
                'integer',
                $projectId ? new BelongsToProject(UnitType::class, $projectId, 'validation.unit_type_belongs_to_project') : null,
            ]),
        ];
    }
}
