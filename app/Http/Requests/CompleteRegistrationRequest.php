<?php

namespace App\Http\Requests;

use App\Http\Requests\FormRequest;
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
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
            'firstname' => 'string|between:2,120',
            'lastname' => 'nullable|string|between:2,120',
            'phone' => 'nullable|string|between:2,24',
            'legal_id' => 'nullable|string|between:2,16',
            'avatar' => 'nullable|image|max:2048', // 2MB max
        ];
    }
}
