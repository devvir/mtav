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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
            // A support account is invited to no project: whatever was sent to its address is not for it
            'invitations' => Invitation::pending()->where('email', $user->isSuperadmin() ? null : $user->email)
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
            'limits'         => $request->user()->isSuperadmin() ? null : config('limits.unapproved'),
        ]);
    }

    public function store(ProjectRequest $request, ProjectService $projects, InvitationService $invitations): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $user = $request->user();

        // Anyone else is its first admin; a superadmin takes no place in it, and invites who is
        $first = $user->isSuperadmin() ? $request->validate([
            'first_admin_email' => ['required', 'email', 'max:255'],
        ]) : null;

        // One whole: an invitation refused leaves no project behind, holding its name
        $project = DB::transaction(function () use ($projects, $invitations, $request, $user, $first) {
            $project = $projects->create($user, $request->validated());

            if ($first) {
                try {
                    $invitations->invite($project, $user, ['email' => $first['first_admin_email']], null, asAdmin: true);
                } catch (ValidationException $refused) {
                    // Said where the page asks for it
                    throw ValidationException::withMessages(['first_admin_email' => collect($refused->errors())->flatten()->all()]);
                }
            }

            return $project;
        });

        return to_route('manage.overview', $project)
            ->with('success', __('flash.project_created'));
    }
}
