<?php

namespace App\Http\Requests;

/**
 * @property-read string $description
 */
class UpdateMediaRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'description' => 'required|string|between:2,255',
        ];
    }
}
