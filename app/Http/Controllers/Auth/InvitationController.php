<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\EditInvitationRequest;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class InvitationController
{
    /**
     * Show the invitation confirmation form or authenticate user with credentials.
     */
    public function edit(EditInvitationRequest $request): Response|RedirectResponse
    {
        $user = $request->invitedUser;
        $user->loadMissing('projects');

        return inertia('Auth/CompleteRegistration', [
            'user'   => $user,
            'family' => $user->asMember()?->family,
        ]);
    }

    /**
     * Update user profile and complete registration.
     */
    public function update(
        CompleteRegistrationRequest $request,
        InvitationService $service
    ): RedirectResponse {
        $service->completeRegistration(
            $request->user(),
            $request->password,
            $request->validated(),
        );

        return to_route('dashboard')
            ->with('success', __('flash.registration_complete'));
    }
}
