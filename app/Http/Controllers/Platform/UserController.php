<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
                'verified'       => $user->hasVerifiedEmail(),
                'projects_count' => $user->memberships_count,
                'created_at'     => $user->created_at,
            ])),
            'filters' => ['q' => $request->q, 'superadmins' => $request->boolean('superadmins')],
        ]);
    }

    /** Who manages the whole platform: a page of its own, for a rare decision */
    public function superadmins(): Response
    {
        return inertia('Platform/Superadmins', [
            'superadmins' => User::where('is_superadmin', true)->alphabetically()->get()
                ->map(fn (User $user) => $user->only('id', 'name', 'email')),
        ]);
    }

    /** Giving the role to an account named by its exact email */
    public function grant(Request $request): RedirectResponse
    {
        // Emails are kept in lower case
        $email = strtolower($request->validate(['email' => ['required', 'email']])['email']);
        $user = User::where('email', $email)->first()
            ?? throw ValidationException::withMessages(['email' => __('validation.no_account_with_email')]);

        $user->forceFill(['is_superadmin' => true])->save();

        return back()->with('success', __('flash.superadmin_granted', ['name' => $user->name]));
    }

    public function superadmin(Request $request, User $user): RedirectResponse
    {
        $grant = $request->validate(['grant' => ['required', 'boolean']])['grant'];

        if (! $grant && $user->is($request->user())) {
            throw ValidationException::withMessages(['grant' => __('validation.cannot_revoke_self')]);
        }

        $user->forceFill(['is_superadmin' => $grant])->save();

        return back()->with('success', __($grant ? 'flash.superadmin_granted' : 'flash.superadmin_revoked', ['name' => $user->name]));
    }
}
