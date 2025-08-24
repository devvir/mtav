<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateFamilyRequest;
use App\Http\Requests\UpdateFamilyRequest;
use App\Models\Family;
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
        setState('groupMembers', true);

        $pool = state('project') ? state('project')->families() : Family::query();

        $families = $pool->orderBy('name')
            ->with(['members' => fn ($query) => $query->alphabetically()])
            ->when($request->string('q'), fn ($query, $q) => $query->search($q, true));

        return inertia('Families/Index', [
            'families' => Inertia::deepMerge(fn () => $families->paginate(20)->withQueryString()),
            'q'        => $request->string('q', ''),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Family $family): Response
    {
        $family->load('members');

        return inertia('Families/Show', compact('family'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Families/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateFamilyRequest $request): RedirectResponse
    {
        $family = Family::create($request->validated());

        return to_route('families.show', $family->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Family $family): Response
    {
        return inertia('Families.Edit', compact('family'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFamilyRequest $request, Family $family): RedirectResponse
    {
        Family::update($request->validated());

        return to_route('families.show', $family->id);
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
