<?php

namespace App\Http\Middleware\Auth;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProjectMustBeSelected
{
    /**
     * For pages that require a Project to be selected:
     *  - If no selected project, redirect to Project index
     *  - Otherwise, continue with this request
     *
     * Note that middleware HandleProjects runs before and ensures that only valid
     * projects are selected, plus it auto-picks the Project when there is only one
     * option. Members/Admins without a valid project are logged out there as well.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Project::current()) {
            return to_route('projects.index');
        }

        return $next($request);
    }
}
