<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\PersonRules;
use Illuminate\Foundation\Http\FormRequest;

/** A new password chosen from the link that resets it */
class NewPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => PersonRules::newPassword(),
        ];
    }
}
