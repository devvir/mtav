<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class ProfileController
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return inertia('Settings/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $email = strtolower($request->email);

        if ($email !== strtolower($user->email)) {
            $user->email_verified_at = null;
        }

        $user->fill([
            ...$request->validated(),
            'email' => $email,
        ])->save();

        return to_route('profile.edit')->with('success', 'Profile updated!');
    }
}
