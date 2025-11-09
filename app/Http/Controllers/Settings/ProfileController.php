<?php

namespace App\Http\Controllers\Settings;

use App\Http\Requests\Settings\ProfileUpdateRequest;
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
        $user = $request->user();
        $data = $request->validated();

        if (strtolower($request->email) !== strtolower($user->email)) {
            $data['email_verified_at'] = null;
        }

        $request->user()->update($data);

        return to_route('profile.edit')
            ->with('success', __('flash.profile_updated'));
    }
}
