<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateFamilyRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(FilteredIndexRequest $request): Response
    {
        setState('groupMembers', true);

        $families = Family::alphabetically()
            ->withMembers()
            ->with(['members' => fn ($q) => $q->alphabetically()])
            ->when($request->project_id, fn ($q, int $id) => $q->inProject($id))
            ->when($request->q, fn ($q, $search) => $q->search($search, searchMembers: true));

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
    public function create(): Response
    {
        return inertia('Families/Create', [
            'projects' => Project::alphabetically()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFamilyRequest $request): RedirectResponse
    {
        $family = Family::create($request->validated());

        return to_route('families.show', $family->id)
            ->with('success', __('New family created!'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Family $family): Response
    {
        return inertia('Families/Edit', [
            'family' => $family->load('project'),
            'projects' => Project::alphabetically()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        $family->update($request->validated());

        return redirect()->back()
            ->with('success', __('Family information updated!'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Family $family): RedirectResponse
    {
        $family->delete();

        return to_route('families.index')
            ->with('success', __('Family deleted!'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(Family $family): RedirectResponse
    {
        $family->restore();

        return to_route('families.show', $family->id)
            ->with('success', __('Family restored!'));
    }
}
