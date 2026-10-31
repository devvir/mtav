<?php

namespace App\Http\Middleware;

use App\Support\Variant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A route of a part of the app this variant does not have does not exist.
 */
class EnsureModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless(Variant::has($module), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
