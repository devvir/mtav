<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @property-read string|null $email
 * @property-read string|null $token
 * @property-read User $invitedUser
 */
class EditInvitationRequest extends FormRequest
{
    /**
     * Validate that either:
     *
     *   1. An invited user is already authenticated
     *   2. A valid ivnited User's email+token pair was passed
     */
    protected function prepareForValidation(): void
    {
        if ($this->email && $this->token) {
            $this->attemptToAuthenticate($this->email, $this->token);
        }

        if (! $this->user()?->isInvited()) {
            $this->failValidation();
        }

        $this->merge(['invitedUser' => $this->user()]);
    }

    protected function attemptToAuthenticate(string $email, string $token): void
    {
        if ($this->user()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        Auth::attempt(['email' => $email, 'password' => $token]);
    }

    /**
     * Fail the validation manually.
     */
    protected function failValidation(): void
    {
        $validator = validator([], []);

        throw new ValidationException(
            $validator,
            redirect()->route('login')
                ->with('error', __('validation.invalid_invitation_credentials'))
        );
    }
}
