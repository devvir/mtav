<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Invitation;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * One's projects, invitations waiting for an answer, and starting a project.
 */
class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return inertia('Projects/Index', [
            'projects' => ProjectResource::collection(
                $user->projects()->withCount('members', 'families')->orderBy('name')->get()
            ),
            'invitations' => Invitation::pending()->where('email', $user->email)
                ->with('project:id,name', 'family:id,name', 'inviter')->get()
                ->map(fn (Invitation $invitation) => [
                    'id'         => $invitation->id,
                    'project'    => $invitation->project->name,
                    'family'     => $invitation->family?->name,
                    'is_admin'   => $invitation->is_admin,
                    'inviter'    => $invitation->inviter?->name,
                    'expires_at' => $invitation->expires_at,
                ]),
            'canCreate' => $user->can('create', Project::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Project::class);

        return inertia('Projects/Create', [
            'limits' => $request->user()->isSuperadmin() ? null : config('limits.unapproved'),
        ]);
    }

    public function store(ProjectRequest $request, ProjectService $projects): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $project = $projects->create($request->user(), $request->validated());

        return to_route('manage.overview', $project)
            ->with('success', __('flash.project_created'));
    }
}
