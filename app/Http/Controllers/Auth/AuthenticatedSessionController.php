<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Support\DemoSignIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        // Over the landing page
        return HomeController::welcome('login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /** No password: only a demo account, only where demo sign-in exists. Kept signed in, as testers would ask */
    public function demo(Request $request): RedirectResponse
    {
        abort_unless(DemoSignIn::allowed(), 404);

        $user = User::find($request->integer('user'));

        abort_unless($user && DemoSignIn::isDemo($user) && ! $user->isBlocked(), 404);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /** This device's notifications stop with the session; the browser gave which one it is */
    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->pushSubscriptions()->where('endpoint', $request->string('endpoint'))->delete();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home');
    }
}
