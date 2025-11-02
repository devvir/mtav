<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\CompleteRegistrationRequest;
use App\Http\Requests\ShowInvitationRequest;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class InvitationController
{
    public function __construct(
        protected InvitationService $invitationService
    ) {
    }

    /**
     * Show the invitation confirmation form.
     */
    public function show(ShowInvitationRequest $request): Response|RedirectResponse
    {
        $email = $request->validated('email');
        $token = $request->validated('token');

        // If email/token provided, authenticate the user
        if ($email && $token) {
            $this->invitationService->logoutCurrentUser();

            $user = $this->invitationService->authenticateInvitedUser($email, $token);

            if ($user instanceof RedirectResponse) {
                return $user;
            }

            // Redirect to clean URL to reload the page with authenticated session
            // This ensures Laravel handles the session/request properly
            return redirect()->route('invitation.show');
        }

        // No email/token in URL - user should already be authenticated from previous redirect
        // TODO: Add invitation expiration check (e.g., token age, session timeout)
        if (! Auth::check()) {
            return redirect()->route('login')
                ->with('error', __('Invitation expired or invalid. If you received an invitation, please check your email and follow the link.'));
        }

        $user = Auth::user();

        // If already verified, redirect them away
        if ($user->isVerified()) {
            return $this->invitationService->alreadyVerifiedResponse();
        }

        $this->invitationService->loadUserRelationships($user);

        return inertia('Auth/CompleteRegistration', [
            'user'  => $user,
            'token' => session('_invitation_token'),
        ]);
    }

    /**
     * Complete the registration process.
     */
    public function store(CompleteRegistrationRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();

        $this->invitationService->completeRegistration($user, $request->validated());

        // Re-login the user to update the session with the fresh database state
        // This ensures the middleware sees the updated invitation_accepted_at
        Auth::logout();
        Auth::login($user->fresh());

        return $this->invitationService->registrationCompletedResponse();
    }
}
