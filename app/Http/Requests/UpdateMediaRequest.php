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
            'description' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => __('validation.media_description_required'),
            'description.max' => __('validation.media_description_too_long'),
        ];
    }
}
