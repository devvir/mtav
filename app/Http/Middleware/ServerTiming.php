<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * In local development only: how long a request took (starting the app, then handling
 * it), and how much of it was the database (queries and their count), in the
 * Server-Timing header the browser's DevTools show beside each request.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $queries = 0;
        $dbMs = 0.0;
        DB::listen(function ($query) use (&$queries, &$dbMs) {
            $queries++;
            $dbMs += $query->time;
        });
        $start = microtime(true);

        $response = $next($request);

        $totalMs = (microtime(true) - $start) * 1000;
        $response->headers->set('Server-Timing', sprintf(
            'boot;dur=%.1f, app;dur=%.1f, db;dur=%.1f;desc="%d queries"',
            ($start - LARAVEL_START) * 1000,
            $totalMs,
            $dbMs,
            $queries,
        ));

        return $response;
    }
}
