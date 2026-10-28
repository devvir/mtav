<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return inertia('Settings/Profile', [
            'profile' => $user->only('firstname', 'lastname', 'email', 'new_email', 'phone', 'email_public', 'phone_public', 'legal_id', 'about'),
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
            $user->notify((new VerifyEmailNotification($newEmail)));

            return back()->with('info', __('flash.email_verification_sent', ['email' => $newEmail]));
        }

        return back()->with('success', __('flash.profile_updated'));
    }
}
