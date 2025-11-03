<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureInvitationAccepted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip middleware for invitation and documentation routes
        if ($request->routeIs('invitation.show') || $request->routeIs('invitation.store') || $request->routeIs('documentation.*')) {
            return $next($request);
        }

        $user = $request->user();

        // If no user is authenticated (guest), let the 'auth' middleware handle it
        if (! $user) {
            return $next($request);
        }

        // If user has accepted invitation, allow them through
        if ($user->acceptedInvitation()) {
            return $next($request);
        }

        // User is authenticated but hasn't accepted invitation
        // If visiting login page, log them out (provides "way out")
        if ($request->routeIs('login')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('warning', __('If you were invited to a project, please follow the link you received in your email.'));
        }

        // For all other pages, redirect back to invitation to complete registration
        return redirect()->route('invitation.show')
            ->with('info', __('Please complete your registration to continue.'));
    }
}
