<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Where people land after signing in: superadmins in the platform console; others in
 * their project if they take part in exactly one, otherwise in the list of them.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

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
}
