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
        $project = Project::current()
            ->loadCount('admins', 'members', 'families', 'unitTypes', 'units', 'events');

        return inertia('Dashboard', [
            'project'   => $project,
            'admins'    => Inertia::lazy(fn () => $project->admins()->get()),
            'members'   => Inertia::lazy(fn () => $project->members()->with('family')->latest()->take(10)->get()),
            'families'  => Inertia::lazy(fn () => $project->families()->withCount('members')->latest()->take(10)->get()),
            'unitTypes' => Inertia::lazy(fn () => $project->unitTypes()->with('units')->get()),
            'events'    => Inertia::lazy(fn () => $project->events()->published()->latest()->take(2)->get()),
            'media'     => 0,  // Not implemented yet
        ]);
    }
}
