<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Each request in the language its person picked, else the platform's (config app.locale)
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $picked = $request->user()?->locale;

        if ($picked && array_key_exists($picked, config('app.locales'))) {
            app()->setLocale($picked);
        }

        return $next($request);
    }
}
