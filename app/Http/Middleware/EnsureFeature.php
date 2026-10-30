<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A place of the project only exists while one of its features is on (others see a 404, as
 * if it did not exist).
 */
class EnsureFeature
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        /** @var Project $project */
        $project = $request->route('project');

        abort_unless(collect($features)->contains(fn (string $feature) => $project->hasFeature($feature)), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
