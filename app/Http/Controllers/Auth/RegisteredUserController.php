<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\User;
use App\Notifications\AccountExistsNotice;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

/**
 * Open sign-up: anyone can create an account (and, once verified, start a project). Whether an
 * address has an account is nobody's business: the answer is the same either way, a link
 * emailed. A new account gets the link that confirms it, signing in to follow it; the owner of
 * one that exists is told where to sign in, and nothing is created.
 */
class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        // Over the landing page
        return HomeController::welcome('register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'email'     => ['required', 'string', 'email', 'max:255'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);
        $email = strtolower(trim($data['email']));

        $taken = User::firstWhere('email', $email) ?? $this->open($data);

        // A blocked account is told nothing; anyone else hears of it once an hour at most
        if ($taken && ! $taken->isBlocked()) {
            RateLimiter::attempt('account-exists:' . sha1($email), 1, fn () => $taken->notify(new AccountExistsNotice()), 3600);
        }

        return to_route('login')->with('info', __('flash.signed_up', ['email' => $email]));
    }

    /** Opens the account (null), or finds the one that took the address at that very moment */
    private function open(array $data): ?User
    {
        try {
            $user = User::create($data);
        } catch (UniqueConstraintViolationException) {
            return User::firstWhere('email', strtolower(trim($data['email'])));
        }

        event(new Registered($user));

        return null;
    }
}
