<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Someone's own profile. A new email address asks for the password: it replaces the current
 * one once confirmed from the new inbox.
 */
class ProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...PersonRules::name(),
            'email' => PersonRules::email(),
            'phone' => ['nullable', 'string', 'between:4,30'],
            // Whether the people of their projects see them (admins always do)
            'email_public'     => ['boolean'],
            'phone_public'     => ['boolean'],
            'legal_id'         => ['nullable', 'string', 'between:2,30'],
            'about'            => ['nullable', 'string', 'max:1000'],
            'current_password' => $this->changesEmail() ? ['required', 'current_password'] : [],
        ];
    }

    /** The address typed, as it would be kept (null: not one) */
    public function newEmail(): ?string
    {
        $email = $this->input('email');

        return is_string($email) ? strtolower($email) : null;
    }

    /** Asking for an address other than theirs, and other than the one already waiting */
    public function changesEmail(): bool
    {
        $user = $this->user();

        return $this->newEmail() !== $user->email && $this->newEmail() !== $user->new_email;
    }

    /** What is changed at once: all but the address, which waits for its confirmation */
    public function profile(): array
    {
        return $this->safe()->except('email', 'current_password');
    }
}
