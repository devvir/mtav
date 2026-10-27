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
 * {project} parameter for every URL generated during the request, frontend included.
 */
class ResolveProject
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Project $project */
        $project = $request->route('project');

        abort_unless($request->user()?->canAccess($project), Response::HTTP_NOT_FOUND);

        Context::addHidden('project', $project);
        URL::defaults(['project' => $project->id]);

        return $next($request);
    }
}
