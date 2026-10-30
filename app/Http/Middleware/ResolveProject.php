<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * For routes under /projects/{project}: only people taking part in the project get
 * in (others see a 404, as if it did not exist). The project becomes the default
 * {project} parameter for every URL generated during the request, frontend included,
 * and the one the person's own pages (profile, notifications) keep in their frame. In a
 * project without guests, someone with no family creates theirs before anything else.
 */
class ResolveProject
{
    /** All there is for someone let into a project without guests, until they have a family */
    private const WITHOUT_FAMILY = ['family.create', 'families.store', 'membership.leave'];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Project $project */
        $project = $request->route('project');

        abort_unless($request->user()?->canAccess($project), Response::HTTP_NOT_FOUND);

        Context::addHidden('project', $project);
        URL::defaults(['project' => $project->id]);
        $request->session()->put('last_project', $project->id);

        if ($request->user()->needsFamilyIn($project) && ! $request->routeIs(self::WITHOUT_FAMILY)) {
            return to_route('family.create', $project);
        }

        return $next($request);
    }
}
