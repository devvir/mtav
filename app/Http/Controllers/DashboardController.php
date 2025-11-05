<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    /**
     * Show the current Project's Dashboard.
     */
    public function __invoke(): Response
    {
        $project = Project::current();
        $project->loadCount('admins', 'members', 'families', 'unitTypes', 'units');

        return inertia('Dashboard', [
            'project' => $project,

            'admins' => Inertia::lazy(
                fn () => $project->admins()->get()
            ),
            'members' => Inertia::lazy(
                fn () => $project->members()->with('family')->latest()->take(10)->get()
            ),
            'families' => Inertia::lazy(
                fn () => $project->families()->withCount('members')->latest()->take(10)->get()
            ),
            'unitTypes' => Inertia::lazy(
                fn () => $project->unitTypes()->with('units')->get()
            ),

            'media' => 0,  // Not implemented yet
            'events' => 0, // Not implemented yet
            'logs' => 0, // Not implemented yet
        ]);
    }
}
