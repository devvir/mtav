<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonRules;
use App\Models\SupportInvitation;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * The link to a support account: whoever has it chooses a name and a password, and the account
 * is made a superadmin, in no project. Only for an address with no account.
 */
class SupportInvitationController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $invitation = SupportInvitation::findByToken($token);

        if (! $invitation?->isPending() || User::where('email', $invitation->email)->exists()) {
            return inertia('Auth/InvitationInvalid');
        }

        return inertia('Auth/Invitation', [
            'token'      => $token,
            'support'    => true,
            'invitation' => [
                'email'     => $invitation->email,
                'firstname' => null,
                'lastname'  => null,
                'is_admin'  => false,
                'project'   => config('app.name'),
                'family'    => null,
                'inviter'   => $invitation->inviter?->name,
            ],
            'mismatch' => (bool) $request->user(),
        ]);
    }

    public function accept(Request $request, string $token, AccountService $accounts): RedirectResponse
    {
        $invitation = SupportInvitation::findByToken($token);
        abort_unless($invitation?->isPending() && ! $request->user(), 404);

        // Checked once the link is known good: a stale link answers the same whatever is sent
        $person = $request->validate(PersonRules::account());

        try {
            $accounts->open(
                $person,
                $invitation->email,
                granted: ['is_superadmin' => true, 'email_verified_at' => now()],
                with: fn () => $invitation->update(['accepted_at' => now()]),
            );
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['password' => __('validation.support_email_taken')]);
        }

        return to_route('admin.projects.index')->with('success', __('flash.support_account_ready'));
    }
}
