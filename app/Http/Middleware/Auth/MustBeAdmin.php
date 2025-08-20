<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MustBeAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::user()?->isAdmin()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Not Found'], Response::HTTP_NOT_FOUND);
            }

            return redirect()->back();
        }

        return $next($request);
    }
}
