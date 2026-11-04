<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Notifications\EmailChangeNotice;
use App\Notifications\VerifyEmailNotification;
use App\Services\AccountService;
use App\Services\Push\PushService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class ProfileController extends Controller
{
    private const EMAIL_CHANGES_PER_HOUR = 5;

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
            // Which browsers send them to this person: a browser alone does not know
            'pushDevices' => $user->pushSubscriptions()->pluck('endpoint'),
        ]);
    }

    /**
     * A new email address replaces the current one once confirmed from the new inbox, and
     * asking for it takes the password. Whether another account has it shows only then: this
     * form tells nobody who has an account. Going back to the current one drops what was
     * pending.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $email = $request->input('email');
        $newEmail = is_string($email) ? strtolower($email) : null;
        $changing = $newEmail !== $user->email && $newEmail !== $user->new_email;

        $data = $request->validate([
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'email'     => ['required', 'email', 'max:255'],
            'phone'     => ['nullable', 'string', 'between:4,30'],
            // Whether the people of their projects see them (admins always do)
            'email_public'     => ['boolean'],
            'phone_public'     => ['boolean'],
            'legal_id'         => ['nullable', 'string', 'between:2,30'],
            'about'            => ['nullable', 'string', 'max:1000'],
            'current_password' => $changing ? ['required', 'current_password'] : [],
        ]);

        unset($data['email'], $data['current_password']);

        $changing && $this->ensureMayChangeEmail($user->id);

        $user->update($data);

        if ($changing) {
            $user->update(['new_email' => $newEmail]);
            // The link to the new address (owning it is what it proves); word of it to the old
            $user->notify(new VerifyEmailNotification($newEmail));
            $user->notify(new EmailChangeNotice($newEmail));

            return back()->with('info', __('flash.email_verification_sent', ['email' => $newEmail]));
        }

        $newEmail === $user->email && $user->update(['new_email' => null]);

        return back()->with('success', __('flash.profile_updated'));
    }

    /**
     * Someone deletes their own account, giving their password: their memberships, devices and answers go with
     * it; what they gave their projects (files, events, lottery records) stays there,
     * without their name. Never the deployment's owner, nor a project's only admin (it
     * would be left without anyone to run it).
     */
    public function destroy(Request $request, AccountService $accounts): RedirectResponse
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'current_password']]);

        if ($user->is_owner) {
            throw ValidationException::withMessages(['confirmation' => __('validation.owner_stays')]);
        }

        $accounts->deleteIfItCanGo($user, first: fn () => Auth::logout());
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('success', __('flash.own_account_deleted'));
    }

    /** Each change mails an address they typed: a few an hour, never a way to send mail */
    private function ensureMayChangeEmail(int $userId): void
    {
        $key = "email-change:{$userId}";

        if (RateLimiter::tooManyAttempts($key, self::EMAIL_CHANGES_PER_HOUR)) {
            throw ValidationException::withMessages(['email' => __('validation.email_changes_limit')]);
        }

        RateLimiter::hit($key, 3600);
    }
}
