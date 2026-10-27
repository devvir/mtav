<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Confirms the address given at sign-up, or a new address set in the profile
 * (which replaces the old one only once confirmed).
 */
class EmailVerificationController extends Controller
{
    public function notice(Request $request): Response|RedirectResponse
    {
        return $request->user()->hasVerifiedEmail()
            ? to_route('home')
            : inertia('Auth/VerifyEmail', ['email' => $request->user()->email]);
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        $user->hasVerifiedEmail() || $user->sendEmailVerificationNotification();

        return back()->with('success', __('flash.verification_sent'));
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless((string) $user->id === $id, 403);

        if ($user->new_email && hash_equals(sha1($user->new_email), $hash)) {
            abort_if(User::where('email', $user->new_email)->exists(), 409, __('validation.email_taken'));

            $user->forceFill(['email' => $user->new_email, 'new_email' => null, 'email_verified_at' => now()])->save();

            return to_route('profile.edit')->with('success', __('flash.email_changed'));
        }

        abort_unless(hash_equals(sha1($user->email), $hash), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return to_route('home')->with('success', __('flash.email_verified'));
    }
}
