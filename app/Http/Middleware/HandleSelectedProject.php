<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auto-pick project if there's only one option to choose, or unset it if the current
 * pick is invalid (member who isn't in the project or admin who doesn't manage it).
 *
 * 1. If the User (Member or regular Admin) has no Projects, log them out
 * 2. The Users exits this middleware with either a valid pick or no pick at all
 */
class HandleSelectedProject
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        // Make sure if there is a picked Project it is valid, otherwise unset it
        $this->sanitizeCurrentProject($user);

        // Admin or member without Project (deleted?) > Forced Logout
        if ($user && $this->userHasNoProjects($user)) {
            Auth::logout();

            return to_route('login');
        }

        return $next($request);
    }

    /**
     * Verify or update Current Project to ensure it is valid for the current User.
     */
    protected function sanitizeCurrentProject(?User $user): void
    {
        if ($this->shouldForceCurrentProject($user)) {
            defineState('project', $user->projects->last());
        } elseif ($this->shouldResetCurrentProject($user)) {
            defineState('project', null);
        } else {
            Project::current()?->fresh();
        }
    }

    /**
     * Whether the Current Project should be forcefully set (if only one option).
     */
    protected function shouldForceCurrentProject(?User $user): bool
    {
        return $user && $this->userHasOneProject($user) && ! Project::current();
    }

    /**
     * Whether the Current Project should be forcefully unset (if invalid).
     */
    protected function shouldResetCurrentProject(?User $user): bool
    {
        return ! $user || ! $this->userHasAccessToCurrentProject($user);
    }

    protected function userHasOneProject(User $user): bool
    {
        return $user->isMember() || $user->projects->count() === 1;
    }

    protected function userHasNoProjects(User $user): bool
    {
        return $user->isNotSuperadmin() && $user->projects->isEmpty();
    }

    protected function userHasAccessToCurrentProject(User $user): bool
    {
        return $user->isSuperadmin() || $user->projects->contains(Project::current());
    }
}
