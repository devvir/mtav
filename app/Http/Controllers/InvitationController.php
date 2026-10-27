<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Response;

/**
 * The emailed invitation link. Without an account, the person picks a password and
 * joins; with one, they join after signing in as the invited address.
 */
class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations)
    {
    }

    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation?->isPending()) {
            return inertia('Auth/InvitationInvalid');
        }

        $invitation->load('project:id,name', 'family:id,name', 'inviter:id,firstname,lastname');
        $user = $request->user();
        $hasAccount = User::where('email', $invitation->email)->exists();

        if (! $user && $hasAccount) {
            redirect()->setIntendedUrl(route('invitation.show', $token));

            return to_route('login')->with('info', __('flash.invitation_sign_in', ['email' => $invitation->email]));
        }

        return inertia('Auth/Invitation', [
            'token'      => $token,
            'invitation' => [
                'email'     => $invitation->email,
                'firstname' => $invitation->firstname,
                'lastname'  => $invitation->lastname,
                'is_admin'  => $invitation->is_admin,
                'project'   => $invitation->project->name,
                'family'    => $invitation->family?->name,
                'inviter'   => $invitation->inviter?->name,
            ],
            /** Signed in as someone else: the invitation is not theirs to accept */
            'mismatch' => $user && $user->email !== $invitation->email,
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = Invitation::findByToken($token);

        abort_unless($invitation?->isPending(), 404);

        $user = $request->user() ?? $this->createAccount($request, $invitation);

        abort_unless($user->email === $invitation->email, 403);

        $this->invitations->accept($invitation, $user);

        return to_route('projects.show', $invitation->project_id)
            ->with('success', __('flash.invitation_accepted', ['project' => $invitation->project->name]));
    }

    /**
     * Accept or decline, from the projects page, an invitation sent to one's address.
     */
    public function respond(Request $request, Invitation $invitation): RedirectResponse
    {
        abort_unless($invitation->isPending() && $invitation->email === $request->user()->email, 404);

        if (! $request->boolean('accept')) {
            $invitation->delete();

            return back()->with('info', __('flash.invitation_declined'));
        }

        $this->invitations->accept($invitation, $request->user());

        return to_route('projects.show', $invitation->project_id)
            ->with('success', __('flash.invitation_accepted', ['project' => $invitation->project->name]));
    }

    private function createAccount(Request $request, Invitation $invitation): User
    {
        abort_if(User::where('email', $invitation->email)->exists(), 409);

        $data = $request->validate([
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([...$data, 'email' => $invitation->email]);

        Auth::login($user);
        $request->session()->regenerate();

        return $user;
    }
}
