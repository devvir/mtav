<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Entering as a demo account with no password, to try the app where nothing is real: on a
 * developer's machine, dev and staging. Anywhere else it does not exist.
 */
class DemoSignIn
{
    private const DEMO_DOMAINS = ['example.com', 'example.org', 'example.net'];

    public static function allowed(): bool
    {
        $deployment = config('app.deployment');

        if ($deployment === 'production' || ! Variant::has('accounts')) {
            return false;
        }

        return in_array($deployment, ['dev', 'staging'], true) || app()->isLocal();
    }

    public static function isDemo(User $user): bool
    {
        return in_array(strtolower(substr(strrchr($user->email, '@') ?: '', 1)), self::DEMO_DOMAINS, true);
    }

    /**
     * Every demo account, with what it is: support (a superadmin), admin of a project, member of
     * one; several at once when so. By role, then by name.
     *
     * @return Collection<int, array{id: int, name: string, email: string, roles: list<string>}>
     */
    public static function accounts(): Collection
    {
        return User::query()
            ->whereNull('blocked_at')
            ->where(fn ($query) => collect(self::DEMO_DOMAINS)->each(fn ($domain) => $query->orWhere('email', 'like', "%@{$domain}")))
            ->withExists([
                'memberships as admin'  => fn ($query) => $query->where('is_admin', true),
                'memberships as member' => fn ($query) => $query->where('is_admin', false),
            ])
            ->get(['id', 'firstname', 'lastname', 'email', 'is_superadmin'])
            ->map(fn (User $user) => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'roles' => array_keys(array_filter(['support' => $user->is_superadmin, 'admin' => $user->admin, 'member' => $user->member])),
            ])
            // Support first, then admins, then members: each kind found together
            ->sortBy(fn (array $account) => [array_search($account['roles'][0] ?? 'none', ['support', 'admin', 'member', 'none'], true), mb_strtolower($account['name'])])
            ->values();
    }
}
