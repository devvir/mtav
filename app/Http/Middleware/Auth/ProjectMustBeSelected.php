<?php

namespace App\Http\Middleware\Auth;

use BadMethodCallException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ProjectMustBeSelected
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // A Project is already selected
        if (state('project')) {
            return $next($request);
        }

        // User has only one Project: set it as current
        if ($request->user()->isNotSuperAdmin() && $request->user()->projects->count() === 1) {
            setState('project', $request->user()->project);

            return $next($request);
        }

        // Admin/Superadmin requests redirect to the Project index/selection page
        if ($request->user()->isAdmin()) {
            return to_route('projects.index');
        }

        // Regular users with 0 or 2+ Projects should not exist (data inconsistency)
        Auth::logout();

        throw new BadMethodCallException('Cannot set the current Project. Contact an Admin for assistance.');
    }
}
