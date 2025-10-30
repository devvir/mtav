<?php

// Copilot - pending review

namespace App\Http\Controllers;

use App\Http\Resources\AdminResource;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\UnitResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Admin;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;

use Inertia\Inertia;

class DashboardController
{
    /**
     * Show the current Project's Dashboard.
     */
    public function __invoke()
    {
        $project = Project::current();

        // Get core stats
        $stats = [
            'families' => Family::where('project_id', $project->id)->count(),
            'members' => Member::whereHas('family', fn($q) => $q->where('project_id', $project->id))->count(),
            'units' => Unit::where('project_id', $project->id)->count(),
            'unit_types' => UnitType::where('project_id', $project->id)->count(),
            'admins' => Admin::whereHas('projects', fn($q) => $q->where('projects.id', $project->id))->count(),
            'media' => 0, // Mock for now
            'events' => 0, // Not implemented yet
        ];

        // Get preview data (equal amounts for layout matching)
        $previewCount = 10;

        return inertia('Dashboard', [
            'stats' => $stats,
            'families' => Inertia::lazy(fn() => FamilyResource::collection(
                Family::where('project_id', $project->id)
                    ->withCount('members')
                    ->latest()
                    ->take($previewCount)
                    ->get()
            )),
            'members' => Inertia::lazy(fn() => MemberResource::collection(
                Member::whereHas('family', fn($q) => $q->where('project_id', $project->id))
                    ->with('family')
                    ->latest()
                    ->take($previewCount)
                    ->get()
            )),
            'unitTypes' => Inertia::lazy(fn() => UnitTypeResource::collection(
                UnitType::where('project_id', $project->id)
                    ->withCount('units')
                    ->with(['units' => function ($query) {
                        $query->with('family')->orderBy('number');
                    }])
                    ->get()
            )),
            'admins' => Inertia::lazy(fn() => AdminResource::collection(
                Admin::whereHas('projects', fn($q) => $q->where('projects.id', $project->id))
                    ->get()
            )),
        ]);
    }
}
