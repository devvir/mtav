<?php

namespace App\Http\Requests;

/**
 * @property-read string $name
 * @property-read string|null $description
 */
class UpdateUnitTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }
}
