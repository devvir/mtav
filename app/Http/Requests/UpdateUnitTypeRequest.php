<?php

namespace App\Http\Requests;

class UpdateUnitTypeRequest extends CreateUnitTypeRequest
{
    public function rules(): array
    {
        /**
         * UnitType's Create and Update Requests use the exact same rules.
         *
         * @see CreateUnitTypeRequest
         */
        return parent::rules();
    }
}
