<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyPendingEmailRequest extends FormRequest
{
    public function prepareForValidation(): void
    {
        $this->merge([
            'user' => User::findOrFail($this->id),
        ]);

        $hash = sha1($this->user->new_email);

        if (! $this->user->new_email || $hash !== $this->hash) {
            throw new HttpResponseException(
                redirect()->route('login')->with('error', __('validation.email_verification_invalid'))
            );
        }
    }
}
