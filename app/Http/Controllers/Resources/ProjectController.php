<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\IndexProjectsRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
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
            'projects' => Inertia::deepMerge(fn () => $projects->paginate(30)),
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

    public function store(CreateProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(fn () => tap(
            Project::create($request->only('name', 'description', 'organization')),
            fn ($project) => $project->admins()->attach($request->admins)
        ));

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
