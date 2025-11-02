<?php

namespace App\Http\Requests;

use App\Models\UnitType;
use App\Http\Requests\FormRequest;

/**
 */
class DeleteUnitTypeRequest extends FormRequest
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var UnitType|null $unitType */
            $unitType = $this->route('unitType');

            if ($unitType && ($unitType->families()->exists() || $unitType->units()->exists())) {
                $validator->errors()->add(
                    'unit_type',
                    __('Cannot delete unit type that is assigned to families or units. Please reassign them first.')
                );
            }
        });
    }
}
