<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Show the members dashboard.
     */
    public function index(): Response
    {
        $units = Project::current()?->units ?? [];

        return inertia('Units/Index', compact('units'));
    }

    /**
     * Show the project details.
     */
    public function show(Unit $unit): Response
    {
        return inertia('Units/Show', compact('unit'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('Units/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return to_route('units.show', $unit->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit): Response
    {
        return inertia('Units.Edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        Unit::update($request->validated());

        return to_route('units.show', $unit->id);
    }

    /**
     * Delete the resource.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return to_route('units.index');
    }
}
