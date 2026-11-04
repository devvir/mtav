<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\SupportInvitation;
use App\Models\User;
use App\Notifications\SupportInvitationNotification;
use App\Services\AccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/**
 * Every account on the platform.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::withCount('memberships')
            ->when($request->q, fn ($q, $search) => $q->search($search, byEmail: true))
            ->when($request->boolean('superadmins'), fn ($q) => $q->where('is_superadmin', true))
            ->alphabetically()
            ->paginate(40)
            ->withQueryString();

        return inertia('Platform/Users', [
            'users' => JsonResource::collection($users->through(fn (User $user) => [
                'id'             => $user->id,
                'name'           => $user->name,
                'email'          => $user->email,
                'is_superadmin'  => $user->isSuperadmin(),
                'is_owner'       => $user->is_owner,
                'blocked_at'     => $user->blocked_at,
                'verified'       => $user->hasVerifiedEmail(),
                'projects_count' => $user->memberships_count,
                'created_at'     => $user->created_at,
            ])),
            'filters' => ['q' => $request->q, 'superadmins' => $request->boolean('superadmins')],
        ]);
    }

    /** Closing an account for good, its email taken (or opening it again) */
    public function block(Request $request, User $user, AccountService $accounts): RedirectResponse
    {
        $block = $request->validate(['block' => ['required', 'boolean']])['block'];
        $this->ensureNotProtected($request, $user);

        $block ? $accounts->block($user) : $accounts->unblock($user);

        return back()->with('success', __($block ? 'flash.account_blocked' : 'flash.account_unblocked', ['name' => $user->name]));
    }

    /** Taking an account away: bookkeeping, and they may sign up again */
    public function destroy(Request $request, User $user, AccountService $accounts): RedirectResponse
    {
        $this->ensureNotProtected($request, $user);

        $name = $user->name;
        $accounts->deleteIfItCanGo($user, 'account');

        return back()->with('success', __('flash.account_deleted', ['name' => $name]));
    }

    /** Who manages the whole platform: a page of its own, for a rare decision */
    public function superadmins(): Response
    {
        return inertia('Platform/Superadmins', [
            'superadmins' => User::where('is_superadmin', true)->alphabetically()->get()
                ->map(fn (User $user) => $user->only('id', 'name', 'email', 'is_owner')),
        ]);
    }

    /**
     * A support account (a superadmin) is invited, on an address with no account: it is apart
     * from anyone's place in a project. Whoever already has an account uses another address.
     */
    public function grant(Request $request): RedirectResponse
    {
        // Emails are kept in lower case
        $email = strtolower($request->validate(['email' => ['required', 'email']])['email']);

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => __('validation.support_email_taken')]);
        }

        $token = Str::random(48);
        $invitation = DB::transaction(function () use ($request, $email, $token) {
            // A newer invitation replaces any pending one for the same address
            SupportInvitation::where('email', $email)->whereNull('accepted_at')->delete();

            return SupportInvitation::create([
                'email'      => $email,
                'token_hash' => Invitation::hashToken($token),
                'invited_by' => $request->user()->id,
                'expires_at' => now()->addDays(config('limits.invitation_days')),
            ]);
        });

        Notification::route('mail', $email)->notify(new SupportInvitationNotification($invitation, $token));

        return back()->with('success', __('flash.invitation_sent', ['email' => $email]));
    }

    /** The role is taken away here; it is only ever given by an invitation (grant) */
    public function superadmin(Request $request, User $user): RedirectResponse
    {
        $grant = $request->validate(['grant' => ['required', 'boolean']])['grant'];

        if ($grant) {
            throw ValidationException::withMessages(['grant' => __('validation.support_by_invitation')]);
        }

        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['grant' => __('validation.cannot_revoke_self')]);
        }

        // The deployment's first superadmin stays one: nobody could bring them back
        if ($user->is_owner) {
            throw ValidationException::withMessages(['grant' => __('validation.owner_keeps_role')]);
        }

        $user->forceFill(['is_superadmin' => false])->save();

        return back()->with('success', __('flash.superadmin_revoked', ['name' => $user->name]));
    }

    /** Not oneself, nor anyone who runs the platform: their role goes first */
    private function ensureNotProtected(Request $request, User $user): void
    {
        if ($user->is($request->user()) || $user->isSuperadmin() || $user->is_owner) {
            throw ValidationException::withMessages(['account' => __('validation.account_protected')]);
        }
    }
}
