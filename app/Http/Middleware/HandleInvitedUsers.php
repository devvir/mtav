<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HandleInvitedUsers
{
    /**
     * Routes where invited users can roam freely without restriction.
     */
    protected $skippedRoutePatterns = [
        'invitation.*',
        'documentation.*',
    ];

    /**
     * Handle invited users roaming outside their allowed areas.
     *
     * This middleware only acts when an authenticated invited user (hasn't completed
     * registration) tries to access routes outside the invitation flow.
     *
     * - login route: log them out so they can continue as they please
     * - other routes: redirect to invitation to complete registration
     * - invitation routes: let the controller handle it
     * - skipped routes: let them roam freely
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Skip if no user, user completed registration, or route is in skip list
        if (! $user || $user->completedRegistration() || $this->shouldSkipRoute($request)) {
            return $next($request);
        }

        // Invited user is roaming outside allowed areas
        if ($request->routeIs('login')) {
            return $this->logoutInvitedUser($request);
        }

        // Redirect to invitation for all other routes
        return redirect()->route('invitation.edit')->with(
            'info',
            __('Please complete your registration to continue.')
        );
    }

    /**
     * Check if the current route should be skipped.
     */
    protected function shouldSkipRoute(Request $request): bool
    {
        foreach ($this->skippedRoutePatterns as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log out invited user visiting the login page.
     *
     * This provides a "way out" so they can authenticate with other accounts
     * or restart the confirmation process later.
     */
    protected function logoutInvitedUser(Request $request): Response
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with(
            'warning',
            __('If you were invited to a project, please follow the link you received in your email.')
        );
    }
}

