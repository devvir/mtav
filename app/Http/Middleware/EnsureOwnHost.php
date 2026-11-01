<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deployment's links lead to its own address (APP_URL), or the same one with or without
 * www, whatever host a request claims: a link mailed to someone else while answering a
 * request must never point where that request said. A request to any other host (a poisoned
 * one, the health check's own 127.0.0.1) is answered as if to the deployment's address, not
 * refused. Where no deployment is set (development, tests) the app answers on any address.
 */
class EnsureOwnHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $own = parse_url((string) config('app.url'));

        if (config('app.deployment') && isset($own['host']) && ! $this->same($request->getHost(), $own['host'])) {
            $request->headers->set('host', $own['host'] . (isset($own['port']) ? ":{$own['port']}" : ''));
        }

        return $next($request);
    }

    private function same(string $host, string $own): bool
    {
        return strcasecmp(preg_replace('/^www\./i', '', $host), preg_replace('/^www\./i', '', $own)) === 0;
    }
}
