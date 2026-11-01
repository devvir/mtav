<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A list's filters are words: an address with a list of them instead (?q[]=x), which no
 * page links to, is taken as having none rather than failing.
 */
class IgnoreListFilters
{
    private const WORDS = ['q', 'kind', 'category'];

    public function handle(Request $request, Closure $next): Response
    {
        foreach (self::WORDS as $filter) {
            if (is_array($request->query($filter))) {
                $request->query->remove($filter);
            }
        }

        return $next($request);
    }
}
