<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Inertia\Response;

class ProfileController
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(): Response
    {
        return inertia('Settings/Profile');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update(Arr::except($request->validated(), ['email']));

        if (strtolower($request->email) !== strtolower($user->email)) {
            $user->update(['new_email' => $request->email]);

            User::make([ /** Notify user on the NEW email, not the old one */
                'id'    => $user->id,
                'email' => $request->email,
            ])->notify(new VerifyEmailNotification());

            return to_route('profile.edit')->with('updateStatus', 'email-verification-sent');
        }

        return to_route('profile.edit')->with('success', __('flash.profile_updated'));
    }
}
