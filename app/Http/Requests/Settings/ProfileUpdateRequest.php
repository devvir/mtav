<?php

namespace App\Http\Requests\Settings;

use App\Http\Requests\FormRequest;
use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @property-read string      $firstname
 * @property-read string|null $lastname
 * @property-read string|null $legal_id
 * @property-read string|null $phone
 * @property-read string|null $about
 * @property-read string      $email
 */
class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => 'required|string|max:255',
            'lastname'  => 'nullable|string|max:255',
            'legal_id'  => 'nullable|string|max:50',
            'phone'     => 'nullable|string|between:5,20',
            'about'     => 'nullable|string|max:1000',
            'email'     => ['required', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->user()),
            ],
        ];
    }
}
