<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Guests land on the page that presents the platform. People signed in go on: superadmins
 * to the platform console; others to their project if they take part in exactly one,
 * otherwise to the list of them.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse|Response
    {
        $user = $request->user();

        if (! $user) {
            return self::welcome();
        }

        if (! $user->hasVerifiedEmail()) {
            return to_route('verification.notice');
        }

        /** Messages for the page people land on must survive this extra redirect */
        $request->session()->reflash();

        // The projects they are in (archived ones are gone for them): one leads straight there
        $projects = $user->projects()->limit(2)->pluck('projects.id');

        return match (true) {
            $user->isSuperadmin()    => to_route('admin.projects.index'),
            $projects->count() === 1 => to_route('projects.show', $projects->first()),
            default                  => to_route('projects.index'),
        };
    }

    /**
     * The landing page, with signing in (or up) open over it when asked.
     *
     * @param 'login'|'register'|null $dialog
     */
    public static function welcome(?string $dialog = null): Response
    {
        return inertia('Welcome', [
            // Not `auth`: that is every page's signed-in person
            'dialog'  => $dialog,
            'google'  => GoogleController::enabled(),
            'locales' => config('app.locales'),
        ]);
    }
}
