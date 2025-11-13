<?php

namespace App\Http\Requests;

/**
 * @property-read string $name
 * @property-read string $description
 */
class CreateUnitTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'required|string|between:2,255',
            'description' => 'required|string|between:3,200',
        ];
    }
}
