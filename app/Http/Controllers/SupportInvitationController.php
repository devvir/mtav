<?php

namespace App\Http\Controllers;

use App\Models\SupportInvitation;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
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

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = SupportInvitation::findByToken($token);
        abort_unless($invitation?->isPending() && ! $request->user(), 404);

        $data = $request->validate([
            'firstname' => ['required', 'string', 'between:2,80'],
            'lastname'  => ['nullable', 'string', 'between:2,80'],
            'password'  => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = DB::transaction(function () use ($invitation, $data) {
                $user = (new User())->forceFill([...$data, 'email' => $invitation->email, 'is_superadmin' => true, 'email_verified_at' => now()]);
                $user->save();
                $invitation->update(['accepted_at' => now()]);

                return $user;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['password' => __('validation.support_email_taken')]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('admin.projects.index')->with('success', __('flash.support_account_ready'));
    }
}
