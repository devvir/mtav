<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * The password once more, before the console's gravest actions (the `password.confirm` routes).
 * The action is not replayed: they return to where they were, and do it again.
 */
class ConfirmablePasswordController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/ConfirmPassword');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        $request->session()->passwordConfirmed();

        return redirect()->intended(route('home', absolute: false))->with('info', __('flash.password_confirmed'));
    }
}
