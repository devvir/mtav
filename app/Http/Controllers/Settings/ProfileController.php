<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Notifications\EmailChangeNotice;
use App\Notifications\VerifyEmailNotification;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use App\Services\Push\PushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return inertia('Settings/Profile', [
            'profile' => $user->only('firstname', 'lastname', 'email', 'new_email', 'phone', 'email_public', 'phone_public', 'legal_id', 'about'),
            // The languages to pick from, each named in itself; theirs (null: the platform's)
            'languages' => config('app.locales'),
            'language'  => $user->locale,
            // What a browser needs to receive notifications (null: not set up here)
            'pushKey' => PushService::enabled() ? config('services.push.public_key') : null,
        ]);
    }

    /**
     * A new email address replaces the current one once confirmed from the new inbox.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone'     => ['nullable', 'string', 'between:4,30'],
            // Whether the people of their projects see them (admins always do)
            'email_public' => ['boolean'],
            'phone_public' => ['boolean'],
            'legal_id'     => ['nullable', 'string', 'between:2,30'],
            'about'        => ['nullable', 'string', 'max:1000'],
        ]);

        $newEmail = strtolower($data['email']);
        unset($data['email']);

        $user->update($data);

        if ($newEmail !== $user->email && $newEmail !== $user->new_email) {
            $user->update(['new_email' => $newEmail]);
            // The link to the new address (owning it is what it proves); word of it to the old
            $user->notify(new VerifyEmailNotification($newEmail));
            $user->notify(new EmailChangeNotice($newEmail));

            return back()->with('info', __('flash.email_verification_sent', ['email' => $newEmail]));
        }

        return back()->with('success', __('flash.profile_updated'));
    }

    /**
     * Someone deletes their own account: their memberships, devices and answers go with
     * it; what they gave their projects (files, events, lottery records) stays there,
     * without their name. Never the deployment's owner, nor a project's only admin (it
     * would be left without anyone to run it).
     */
    public function destroy(Request $request, AccountService $accounts): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['confirmation' => ['required', Rule::in([$user->email])]], [
            'confirmation.in' => __('validation.confirm_with_email'),
        ]);

        if ($user->is_owner) {
            throw ValidationException::withMessages(['confirmation' => __('validation.owner_stays')]);
        }

        $accounts->ensureCanGo($user);
        Auth::logout();
        $accounts->delete($user);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('success', __('flash.account_deleted'));
    }
}
