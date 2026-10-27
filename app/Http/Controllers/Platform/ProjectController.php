<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Response;

/**
 * Every project on the platform; those awaiting approval come first.
 */
class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = in_array($request->query('show'), ['awaiting', 'unapproved', 'approved']) ? $request->query('show') : 'all';

        $projects = Project::with('creator:id,firstname,lastname,email')
            ->withCount('memberships', 'families', 'units')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($filter === 'awaiting', fn ($q) => $q->whereNull('approved_at')->whereNotNull('approval_requested_at'))
            ->when($filter === 'unapproved', fn ($q) => $q->whereNull('approved_at'))
            ->when($filter === 'approved', fn ($q) => $q->whereNotNull('approved_at'))
            ->orderByRaw('approved_at IS NULL AND approval_requested_at IS NOT NULL DESC')
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return inertia('Platform/Projects', [
            'projects' => JsonResource::collection($projects->through(fn (Project $project) => [
                ...ProjectResource::make($project)->resolve($request),
                'people_count'   => $project->memberships_count,
                'families_count' => $project->families_count,
                'units_count'    => $project->units_count,
                'creator'        => $project->creator?->only('id', 'email') + ['name' => $project->creator?->name],
            ])),
            'counts' => [
                'awaiting' => Project::whereNull('approved_at')->whereNotNull('approval_requested_at')->count(),
            ],
            'filters' => ['q' => $request->q, 'show' => $filter],
        ]);
    }

    public function approve(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $projects->approve($project, $request->user());

        return back()->with('success', __('flash.project_approved', ['project' => $project->name]));
    }
}
