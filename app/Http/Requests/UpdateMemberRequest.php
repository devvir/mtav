<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

/**
 * @property-read string $firstname
 * @property-read string|null $lastname
 * @property-read string $email
 */
class UpdateMemberRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'email'     => ['required', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->route('member')),
            ],
        ];
    }
}
