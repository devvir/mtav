<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Signing in with Google: the account linked to that Google account, else the one with
 * its email (Google vouches for it), else a new one. Offered only where Google is set up.
 */
class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return to_route('login')->with('error', __('auth.google_failed'));
        }

        // An email Google has not verified could be anyone's: it never opens an account
        if (! ($google->user['email_verified'] ?? false)) {
            return to_route('login')->with('error', __('auth.google_unverified'));
        }

        $user = User::firstWhere('google_id', $google->getId())
            ?? User::firstWhere('email', $google->getEmail());

        if ($user) {
            $user->forceFill([
                'google_id'         => $google->getId(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'email'     => $google->getEmail(),
                'firstname' => $google->user['given_name'] ?? Str::before((string) $google->getName(), ' '),
                'lastname'  => $google->user['family_name'] ?? null,
                // Never typed: the account opens with Google (a password can be set later)
                'password' => Str::password(40),
            ]);
            $user->forceFill(['google_id' => $google->getId(), 'email_verified_at' => now()])->save();
            event(new Registered($user));
        }

        Auth::login($user, remember: true);
        request()->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /** Google is set up for this deployment */
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
