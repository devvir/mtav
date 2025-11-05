<?php

namespace App\Http\Requests;

use App\Models\Family;
use App\Models\Unit;
use App\Models\UnitType;
use App\Rules\BelongsToProject;

/**
 * @property-read string|null $number
 * @property-read int|null $unit_type_id
 * @property-read int|null $family_id
 */
class UpdateUnitRequest extends FormRequest
{
    public function rules(): array
    {
        /** @var Unit $unit */
        $unit = $this->route('unit');
        $projectId = $unit->project_id;

        return [
            'number' => 'required|string|max:255',
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
