<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\PersonRules;
use Illuminate\Foundation\Http\FormRequest;

/** Signing up: a name, an address and a password */
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [...PersonRules::account(), 'email' => PersonRules::email()];
    }
}
