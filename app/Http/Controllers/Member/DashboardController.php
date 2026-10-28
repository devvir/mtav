<?php

namespace App\Http\Controllers\Member;

use App\Enums\MediaCategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\MemberResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The project's home: what is coming up and what was shared, and for admins, what is
 * left to set up.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, Project $project, ProjectService $projects): Response
    {
        $user = $request->user();
        $family = $user->familyIn($project);
        $run = $project->activeLotteryRun;

        return inertia('Member/Dashboard', [
            'overview' => [
                'description'    => $project->description,
                'organization'   => $project->organization,
                'families_count' => $project->families()->count(),
                'members_count'  => $project->memberships()->count(),
                'units_count'    => $project->units()->count(),
            ],
            // Admins see what is left to set up, until the lottery has run
            'setup'   => $user->can('update', $project) ? $projects->setupChecklist($project) : null,
            'lottery' => [
                'start_date' => $project->lottery->start_date,
                'status'     => $run?->status->value,
                'unit'       => $family?->unit?->only('id', 'identifier'),
            ],
            'events' => Inertia::defer(fn () => EventResource::collection(
                $project->events()->published()->where('type', '!=', 'lottery')->upcoming()
                    ->with('rsvps')->orderBy('start_date')->limit(3)->get()
            )),
            // Who the project is: a few families, its admins, who joined last
            'community' => Inertia::defer(fn () => $this->community($project)),
            'media'     => Inertia::defer(fn () => MediaResource::collection(
                $project->media()->category(MediaCategory::MEDIA)->with('owner')->latest()->limit(8)->get()
            )),
        ]);
    }

    private function community(Project $project): array
    {
        $withMembership = ['memberships' => fn ($q) => $q->where('project_id', $project->id)->with('family:id,name')];

        return [
            'families' => FamilyResource::collection(
                $project->families()->withCount('members')->alphabetically()->limit(6)->get()
            ),
            'admins' => MemberResource::collection($project->admins()->with($withMembership)->alphabetically()->get()),
            'latest' => MemberResource::collection(
                $project->members()->with($withMembership)->orderByPivot('created_at', 'desc')->limit(6)->get()
            ),
        ];
    }
}
