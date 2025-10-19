<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        updateState('groupMembers', true);

        $pool = Project::current()?->families() ?? Family::query();

        $families = $pool
            ->alphabetically()
            ->with(['members' => fn ($query) => $query->alphabetically()])
            ->when($request->q, fn ($query, $q) => $query->search($q, searchMembers: true));

        return inertia('Families/Index', [
            'families' => Inertia::deepMerge(fn () => $families->paginate(30)),
            'q' => $request->string('q', ''),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Family $family): Response
    {
        $family->load('project', 'members');

        return inertia('Families/Show', compact('family'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $projectsPool = $request->user()->isSuperAdmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Families/Create', [
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFamilyRequest $request): RedirectResponse
    {
        $family = Family::create([
            'name' => $request->name,
            'project_id' => $request->project,
        ]);

        return to_route('families.show', $family->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Family $family): Response
    {
        return inertia('Families/Edit', compact('family'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family): RedirectResponse
    {
        $family->delete();

        return to_route('families.index');
    }
}
