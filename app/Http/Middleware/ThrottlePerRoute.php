<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * Rate limits (`throttle:20,1`) counted per route: Laravel counts every such limit on one counter per person (per
 * address, signed out), so signing in, joining a lottery and ranking would use each other's allowance up, and the
 * strictest limit would stand for all. Named limiters keep their own keys.
 */
class ThrottlePerRoute extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $route = $request->route();

        return sha1($request->method() . ' ' . $route?->uri() . '|' . parent::resolveRequestSignature($request));
    }
}
