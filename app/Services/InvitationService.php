<?php

namespace App\Services;

use App\Events\UserRegistration;
use App\Models\Admin;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationService
{
    /**
     * Create and invite a new admin.
     *
     * @param array{email: string, firstname: string, lastname?: string} $userData
     * @param int $projectIds
     * @return Admin
     */
    public function inviteAdmin(array $userData, array $projectIds): Admin
    {
        $token = $this->createToken();
        $data = ['password' => $token, 'is_admin' => true] + $userData;

        $admin = DB::transaction(function () use ($data, $projectIds) {
            $admin = Admin::create($data);
            $admin->projects()->attach($projectIds);
            return $admin;
        });

        event(new UserRegistration($admin, $token));

        return $admin;
    }

    /**
     * Create and invite a new member.
     *
     * @param array{email: string, firstname: string, lastname?: string, family_id: int} $userData
     * @param int $projectId
     * @return Member
     */
    public function inviteMember(array $userData, int $projectId): Member
    {
        $token = $this->createToken();
        $data = ['password' => $token] + $userData;

        $member = DB::transaction(
            fn () => Member::create($data)->joinProject($projectId)
        );

        event(new UserRegistration($member, $token));

        return $member;
    }

    /**
     * Log out the currently authenticated user.
     */
    public function logoutCurrentUser(): void
    {
        if (Auth::check()) {
            Auth::logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
    }

    /**
     * Authenticate user with email and token (as password).
     * Returns the user if successful, or a redirect response if failed.
     *
     * TODO: Add invitation expiration logic (e.g., check user creation date, token age, etc.)
     */
    public function authenticateInvitedUser(string $email, string $token): User|RedirectResponse
    {
        // Attempt to authenticate with email and token
        if (! Auth::attempt(['email' => $email, 'password' => $token])) {
            return $this->invalidInvitationResponse();
        }

        $user = Auth::user();

        // If already verified, log them out and redirect
        if ($user->isVerified()) {
            Auth::logout();
            return $this->alreadyVerifiedResponse();
        }

        // Store token in session for the form submission
        request()->session()->put('_invitation_token', $token);

        return $user;
    }

    /**
     * Load the necessary relationships for the user.
     */
    public function loadUserRelationships(User $user): User
    {
        $user->load('projects');

        if ($user->isMember()) {
            $user->load('family.project');
        }

        return $user;
    }

    /**
     * Complete the user's registration.
     */
    public function completeRegistration(User $user, array $validatedData): void
    {
        $updateData = [
            'password' => $validatedData['password'],
            'invitation_accepted_at' => now(),
            'email_verified_at' => now(),
        ];

        // Add optional fields if provided
        foreach (['firstname', 'lastname', 'phone', 'legal_id'] as $field) {
            if (isset($validatedData[$field])) {
                $updateData[$field] = $validatedData[$field];
            }
        }

        // Handle avatar upload if present
        if (isset($validatedData['avatar']) && $validatedData['avatar'] instanceof \Illuminate\Http\UploadedFile) {
            $updateData['avatar'] = $validatedData['avatar']->store('avatars', 'public');
        }

        $user->update($updateData);
    }

    /**
     * Return redirect response for invalid invitation.
     */
    protected function invalidInvitationResponse(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('error', __('Invalid invitation link, please contact an administrator.'));
    }

    /**
     * Return redirect response for already verified user.
     */
    public function alreadyVerifiedResponse(): RedirectResponse
    {
        return redirect()->route('home')
            ->with('info', __('Your account is already verified.'));
    }

    /**
     * Return redirect response after successful registration.
     */
    public function registrationCompletedResponse(): RedirectResponse
    {
        return redirect()->route('home')
            ->with('success', __('Welcome! Your registration is complete.'));
    }

    /**
     * Create a new random invitation token.
     */
    private function createToken(): string
    {
        return base64_encode(random_bytes(32));
    }
}
