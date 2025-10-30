<?php

// Copilot - pending review

namespace App\Http\Controllers\Auth;

use App\Http\Requests\CompleteRegistrationRequest;
use App\Models\User;
use App\Services\InvitationTokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class InvitationConfirmationController
{
    /**
     * Show the invitation confirmation form.
     */
    public function show(Request $request): Response|RedirectResponse
    {
        $email = $request->query('email');
        $token = $request->query('token');

        // Validate query parameters
        if (!$email || !$token) {
            return redirect()->route('login')
                ->with('error', __('Invalid invitation link.'));
        }

        // Find user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', __('User not found.'));
        }

        // Verify token
        if (!InvitationTokenService::verify($token, $user->password)) {
            return redirect()->route('login')
                ->with('error', __('Invalid or expired invitation token.'));
        }

        // If already verified, redirect to login
        if ($user->email_verified_at) {
            return redirect()->route('login')
                ->with('info', __('Your account is already active. Please log in.'));
        }

        // Load relationships for display
        if ($user->is_admin) {
            $user->load('projects');
        } else {
            $user->load('asMember.family.project');
        }

        return inertia('Auth/CompleteRegistration', [
            'user' => $user,
            'email' => $email,
            'token' => $token,
        ]);
    }

    /**
     * Complete the registration process.
     */
    public function store(CompleteRegistrationRequest $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->firstOrFail();

        // Verify token again
        if (!InvitationTokenService::verify($request->token, $user->password)) {
            return redirect()->route('login')
                ->with('error', __('Invalid or expired invitation token.'));
        }

        // Update user
        $updateData = [
            'password' => $request->password, // Will be hashed automatically
            'email_verified_at' => now(),
        ];

        // Add optional fields if provided
        if ($request->has('firstname')) {
            $updateData['firstname'] = $request->firstname;
        }
        if ($request->has('lastname')) {
            $updateData['lastname'] = $request->lastname;
        }
        if ($request->has('phone')) {
            $updateData['phone'] = $request->phone;
        }
        if ($request->has('legal_id')) {
            $updateData['legal_id'] = $request->legal_id;
        }

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $path;
        }

        $user->update($updateData);

        return redirect()->route('login')
            ->with('success', __('Registration completed! You can now log in.'));
    }
}
