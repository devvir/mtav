<?php

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;

/**
 * @property-read string $name
 * @property-read string|null $description
 *
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
