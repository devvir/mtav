<?php

namespace App\Http\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read string            $password
 * @property-read string            $firstname
 * @property-read string|null       $lastname
 * @property-read string|null       $phone
 * @property-read string|null       $legal_id
 * @property-read UploadedFile|null $avatar
 */
class CompleteRegistrationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'password'  => ['required', 'confirmed', Password::defaults()],
            'firstname' => 'string|between:2,120',
            'lastname'  => 'nullable|string|between:2,120',
            'phone'     => 'nullable|string|between:2,24',
            'legal_id'  => 'nullable|string|between:2,16',
            'avatar'    => 'nullable|image|max:2048', // 2MB max
        ];
    }
}
