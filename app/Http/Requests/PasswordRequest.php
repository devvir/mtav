<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Someone signed in changing their password, giving the current one */
class PasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password'         => PersonRules::newPassword(),
        ];
    }
}
