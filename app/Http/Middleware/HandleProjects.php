<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-pick project if there's only one option to choose, or unset it if the current
 * pick is invalid (member who isn't in the project or admin who doesn't manage it).
 *
 * 1. If the User (Member or regular Admin) has no Projects, log them out
 * 2. The Users exits this middleware with either a valid pick or no pick at all
 */
class HandleProjects
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        /** @var Collection<Project> $projects */
        $projects = $user?->projects ?? collect();

        // Make sure if there is a picked Project it is valid, otherwise unset it
        if (! Project::current() && ($user?->isMember() || $projects->count() === 1)) {
            setState('project', $projects->last());
        } elseif (! $user?->isSuperadmin() && $projects->doesntContain(Project::current())) {
            setState('project', null);
        }

        // Admin or member without Project (deleted?) > Forced Logout
        if ($user && $user->isNotSuperadmin() && $projects->isEmpty()) {
            Auth::logout();

            return to_route('login');
        }

        return $next($request);
    }
}
