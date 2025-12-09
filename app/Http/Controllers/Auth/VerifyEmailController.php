<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\VerifyPendingEmailRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController
{
    public function __invoke(VerifyPendingEmailRequest $request): RedirectResponse
    {
        $request->user->update([
            'email'             => $request->user->new_email,
            'new_email'         => null,
            'email_verified_at' => now(),
        ]);

        return to_route('profile.edit')->with('updateStatus', 'email-verified');
    }
}
