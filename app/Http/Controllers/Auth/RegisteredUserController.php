<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

/**
 * Open sign-up: anyone can create an account (and, once verified, start a project).
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
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create($data);

        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}
