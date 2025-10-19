<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the project dashboard.
     */
    public function index(Request $request): Response
    {
        $pool = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        $projects = $pool->alphabetically()
            ->withCount('admins', 'members', 'families')
            ->with([
                'admins' => fn ($q) => $q->limit(5),
                'members' => fn ($q) => $q->limit(5),
                'families' => fn ($q) => $q->limit(5),
            ])
            ->when($request->q, fn ($query, $q) => $query->whereLike('name', "%$q%"));

        return inertia('Projects/Index', [
            'projects' => Inertia::deepMerge(fn () => $projects->paginate(30)),
            'q' => $request->string('q', ''),
        ]);
    }

    /**
     * Show the project details.
     */
    public function show(Project $project)
    {
        $project
            ->load([
                'admins' => fn ($q) => $q->limit(10),
                'members' => fn ($q) => $q->limit(50),
                'families' => fn ($q) => $q->limit(30),
            ])
            ->loadCount('admins', 'members', 'families');

        return inertia('Projects/Show', compact('project'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Projects/Create', [
            'admins' => User::whereIsAdmin(true)->alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(fn () => tap(
            Project::query()->create($request->only('name', 'description', 'organization')),
            fn ($project) => $project->admins()->attach($request->admins)
        ));

        return to_route('projects.show', $project->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        return inertia('Projects/Edit', [
            'project' => $project->load('admins'),
            'admins' => User::whereIsAdmin(true)->alphabetically()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

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
