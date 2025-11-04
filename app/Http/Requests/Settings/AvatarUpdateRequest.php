<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\FormRequest;

/**
 * @property-read \Illuminate\Http\UploadedFile $avatar
 */
class AvatarUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'max:2048'], // 2MB max
        ];
    }
}
