<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * @property-read UploadedFile $avatar
 */
class AvatarUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'avatar' => 'required|image|max:2048', // 2MB max
        ];
    }
}
