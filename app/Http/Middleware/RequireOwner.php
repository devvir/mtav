<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deployment is used only once it has an owner, its first superadmin. With no account at
 * all, OWNER_EMAIL / OWNER_PASSWORD create the owner; without them the site stays closed,
 * showing only that it is under construction (its public documents stay readable: the
 * privacy page, the manual, the app's manifest). Once anyone exists, this costs nothing:
 * the answer is kept.
 */
class RequireOwner
{
    private const KNOWN = 'mtav.has-owner';

    /** Known in this process (the cache keeps it for the others) */
    public static bool $owned = false;

    /** Readable even before there is an owner */
    private const PUBLIC = ['privacy', 'manual', 'manifest'];

    public function handle(Request $request, Closure $next): Response
    {
        if (self::$owned || Cache::get(self::KNOWN) || $request->routeIs(...self::PUBLIC)) {
            return $next($request);
        }

        if (! User::query()->exists() && ! $this->createOwner()) {
            return Inertia::render('UnderConstruction')->toResponse($request)
                ->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE);
        }

        self::$owned = true;
        Cache::forever(self::KNOWN, true);

        return $next($request);
    }

    /** The owner from the deployment's settings; whether there is one now */
    private function createOwner(): bool
    {
        ['email' => $email, 'password' => $password] = config('app.owner');
        if (! $email || ! $password) {
            return false;
        }

        try {
            (new User())->forceFill([
                'email'             => strtolower($email),
                'password'          => $password,
                'firstname'         => config('app.owner.name') ?: 'Owner',
                'email_verified_at' => now(),
                'is_superadmin'     => true,
                'is_owner'          => true,
            ])->save();
        } catch (UniqueConstraintViolationException) {
            // Another request created it meanwhile
        }

        return true;
    }
}
