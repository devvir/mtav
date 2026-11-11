<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\PasswordRequest;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    /**
     * A new password signs out every other device; this one stays, remembered if it was. None
     * keeps its notifications: each is turned back on by whoever holds it.
     */
    public function update(PasswordRequest $request, AccountService $accounts): RedirectResponse
    {
        $data = $request->validated();

        $user = $request->user();
        $user->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        Auth::logoutOtherDevices($data['password']);
        $accounts->endSessions($user, except: $request->session()->getId());
        $user->pushSubscriptions()->delete();

        return back()->with('success', __('flash.password_updated'));
    }

    /**
     * A link to one's own inbox, for a password forgotten or never chosen (an account opened
     * with Google). Owning the inbox is what it proves: a session alone gets no new password.
     */
    public function link(Request $request): RedirectResponse
    {
        PasswordBroker::sendResetLink(['email' => $request->user()->email]);

        return back()->with('info', __('passwords.sent'));
    }
}
