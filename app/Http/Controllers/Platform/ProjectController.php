<?php

namespace App\Http\Controllers\Platform;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Resources\Json\JsonResource;
use Inertia\Response;

/**
 * Every project on the platform; those awaiting approval come first. Archived ones (their
 * admins deleted them) are apart: restored from here, or deleted for good.
 */
class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = in_array($request->query('show'), ['awaiting', 'unapproved', 'approved', 'archived']) ? $request->query('show') : 'all';

        $projects = Project::with('creator:id,firstname,lastname,email', 'archiver:id,firstname,lastname')
            ->withCount(['memberships', 'families', 'units', 'media', 'lotteryRuns',
                // Its lottery's own event aside
                'events' => fn ($q) => $q->where('type', '!=', EventType::LOTTERY)])
            ->withSum('media', 'file_size')
            ->when($filter === 'archived', fn ($q) => $q->onlyTrashed())
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
                // What deleting it for good takes away
                'events_count' => $project->events_count,
                'media_count'  => $project->media_count,
                'media_bytes'  => (int) $project->media_sum_file_size,
                'lottery_runs' => $project->lottery_runs_count,
                'archived_at'  => $project->deleted_at,
                'archived_by'  => $project->archiver?->name,
            ])),
            'counts' => [
                'awaiting' => Project::whereNull('approved_at')->whereNotNull('approval_requested_at')->count(),
                'archived' => Project::onlyTrashed()->count(),
            ],
            'filters' => ['q' => $request->q, 'show' => $filter],
        ]);
    }

    public function approve(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $projects->approve($project, $request->user());

        return back()->with('success', __('flash.project_approved', ['project' => $project->name]));
    }

    public function restore(Project $project, ProjectService $projects): RedirectResponse
    {
        $projects->restore($project);

        return back()->with('success', __('flash.project_restored', ['project' => $project->name]));
    }

    /** Deleted for good, once its name is typed */
    public function destroy(Request $request, Project $project, ProjectService $projects): RedirectResponse
    {
        $request->validate(['confirmation' => ['required', Rule::in([$project->name])]]);

        $projects->purge($project);

        return to_route('admin.projects.index', $project->trashed() ? ['show' => 'archived'] : [])
            ->with('success', __('flash.project_purged', ['project' => $project->name]));
    }
}
