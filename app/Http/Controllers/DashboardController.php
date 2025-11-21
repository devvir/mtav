<?php

namespace App\Http\Controllers;

use App\Http\Resources\JsonResource;
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
        $project = Project::current()->fresh();

        return inertia('Dashboard/Dashboard', [
            'stats' => Inertia::defer(fn () => $this->stats($project)),

            ...$this->people($project),
            ...$this->content($project),
            ...$this->structure($project),
        ]);
    }

    protected function stats(Project $project): JsonResource
    {
        $project->loadCount('units', 'admins', 'members', 'families', 'documents', 'visualMedia', 'events');

        return $project->toResource()->only(
            'units_count',
            'families_count',
            'members_count',
            'admins_count',
            'visual_media_count',
            'documents_count',
            'events_count',
        );
    }

    protected function people(Project $project): array
    {
        return [
            'admins' => Inertia::defer(
                fn () => $project->admins()->latest()->take(2)->get()
            ),
            'members' => Inertia::defer(
                fn () => $project->members()->with('family')->latest()->take(10)->get()
            ),
            'families' => Inertia::defer(
                fn () => $project->families()->withCount('members')->latest()->take(10)->get()
            ),
        ];
    }

    protected function content(Project $project): array
    {
        return [
            'media' => Inertia::defer(
                fn () => $project->visualMedia()->latest()->take(5)->get()
            ),
            'events' => Inertia::defer(
                fn () => $project->events()->published()->latest()->take(2)->get()
            ),
        ];
    }

    protected function structure(Project $project): array
    {
        return [
            'unitTypes' => Inertia::defer(
                fn () => $project->unitTypes->load('units')
            ),
        ];
    }
}
