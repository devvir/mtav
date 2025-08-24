<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the project dashboard.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Projects/Index', [
            'projects' => Inertia::deepMerge(fn () => $projects->paginate()),
        ]);
    }

    /**
     * Show the project details.
     */
    public function show(Project $project)
    {
        return inertia('Projects/Show', compact('project'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProjectRequest $request): RedirectResponse
    {
        $project = Project::create($request->validated());

        return to_route('projects.show', $project->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        return inertia('Projects.Edit', compact('project'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        Project::update($request->validated());

        return to_route('projects.show', $project->id);
    }

    /**
     * Delete the resource.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return to_route('projects.index');
    }
}
