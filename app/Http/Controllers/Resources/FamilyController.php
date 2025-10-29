<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateFamilyRequest;
use App\Http\Requests\IndexFamiliesRequest;
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
    public function index(IndexFamiliesRequest $request): Response
    {
        setState('groupMembers', true);

        $families = Family::alphabetically()
            ->when($request->project_id, fn ($q, $projectId) => $q->where('project_id', $projectId))
            ->with(['members' => fn ($query) => $query->alphabetically()])
            ->when($request->q, fn ($query, $q) => $query->search($q, searchMembers: true));

        return inertia('Families/Index', [
            'families' => Inertia::deepMerge(fn () => $families->paginate(30)),
            'q' => $request->q ?? '',
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
        $projectsPool = $request->user()->isSuperadmin()
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
                // Persist Family
        $family = Family::create($request->all());

        return to_route('families.show', $family->id)
            ->with('success', __('Family created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Family $family): Response
    {
        $projectsPool = $request->user()->isSuperadmin()
            ? Project::query()
            : $request->user()->projects();

        return inertia('Families/Edit', [
            'family' => $family->load('project'),
            'projects' => $projectsPool->alphabetically()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->all());

        return redirect()->back()
            ->with('success', __('Family updated successfully.'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family): RedirectResponse
    {
        $family->delete();

        return to_route('families.index')
            ->with('success', __('Family deleted successfully.'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(Family $family): RedirectResponse
    {
        $family->restore();

        return to_route('families.show', $family->id)
            ->with('success', __('Family restored successfully.'));
    }
}
