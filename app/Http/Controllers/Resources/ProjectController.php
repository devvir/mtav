<?php

// Copilot - Pending review

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\IndexProjectsRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use App\Services\InvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the project dashboard.
     */
    public function index(IndexProjectsRequest $request): Response
    {
        $projects = Project::alphabetically()
            ->withCount('members', 'families')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->unless($request->all, fn ($q) => $q->where('projects.active', true));

        return inertia('Projects/Index', [
            'projects' => Inertia::defer(fn () => $projects->paginate(30))->deepMerge(),
            'all'      => $request->boolean('all') ?? false,
            'q'        => $request->q ?? '',
        ]);
    }

    /**
     * Show the project details.
     */
    public function show(Project $project)
    {
        $project->loadCount('members', 'families', 'units', 'media', 'events');

        return inertia('Projects/Show', [
            'project' => $project->load('admins'),
        ]);
    }

    public function create(): Response
    {
        $formSpecs = FormService::make(Project::class, FormType::CREATE);

        return inertia('Projects/Create', [
            'form' => $formSpecs,
        ]);
    }

    public function store(CreateProjectRequest $request, InvitationService $invitationService): RedirectResponse
    {
        $project = DB::transaction(function () use ($request, $invitationService) {
            $project = Project::create($request->only('name', 'description', 'organization'));
            $admins = $request->admins ?? [];

            if ($request->new_admin_email && $request->new_admin_firstname) {
                $admins[] = $invitationService->inviteAdmin([
                    'email'     => $request->new_admin_email,
                    'firstname' => $request->new_admin_firstname,
                    'lastname'  => $request->new_admin_lastname,
                ], [ $project->id ]);
            }

            count($admins) && $project->addAdmins($admins);

            return $project;
        });

        return to_route('projects.show', $project)
            ->with('success', __('flash.project_created'));
    }

    public function edit(Project $project): Response
    {
        $formSpecs = FormService::make($project, FormType::UPDATE);

        return inertia('Projects/Edit', [
            'form' => $formSpecs,
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return to_route('projects.show', $project)
            ->with('success', __('flash.project_updated'));
    }

    public function destroy(Project $project): RedirectResponse
    {
        $inUse = $project->members()->count();

        Gate::denyIf($inUse, __('validation.project_has_active_members'));

        $project->delete();

        return to_route('projects.index')
            ->with('success', __('flash.project_deleted'));
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->restore();

        return to_route('projects.show', $project)
            ->with('success', __('flash.project_restored'));
    }
}
