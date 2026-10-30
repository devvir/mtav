<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Invitation;
use App\Models\Project;
use App\Services\InvitationService;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
                $user->reachableProjects()->withCount('members', 'families')->orderBy('name')->get()
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
            'asksFirstAdmin' => $request->user()->isSuperadmin(),
            'limits' => $request->user()->isSuperadmin() ? null : config('limits.unapproved'),
        ]);
    }

    public function store(ProjectRequest $request, ProjectService $projects, InvitationService $invitations): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $user = $request->user();

        // Anyone else is its first admin; a superadmin says who is: themselves, or someone invited
        $first = $user->isSuperadmin() ? $request->validate([
            'first_admin'       => ['required', Rule::in(['me', 'someone'])],
            'first_admin_email' => ['required_if:first_admin,someone', 'nullable', 'email', 'max:255'],
        ]) : null;

        $project = $projects->create($user, $request->validated());

        if ($first && $first['first_admin'] === 'me') {
            $project->memberships()->create(['user_id' => $user->id, 'is_admin' => true]);
            $user->unsetRelation('memberships');
        } elseif ($first) {
            $invitations->invite($project, $user, ['email' => $first['first_admin_email']], null, asAdmin: true);
        }

        return to_route('manage.overview', $project)
            ->with('success', __('flash.project_created'));
    }
}
