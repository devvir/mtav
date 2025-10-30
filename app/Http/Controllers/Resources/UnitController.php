<?php

// Copilot - pending review

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\IndexUnitsRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Show the resource index.
     */
    public function index(IndexUnitsRequest $request): Response
    {
        $units = Unit::with('type', 'family')
            ->where('project_id', $request->project_id ?? currentProject()->id)
            ->orderBy('number')
            ->get();

        return inertia('Units/Index', [
            'units' => Inertia::deepMerge(fn () => $units),
        ]);
    }

    /**
     * Show the resource details.
     */
    public function show(Unit $unit): Response
    {
        $unit->load('type', 'family', 'project');

        return inertia('Units/Show', compact('unit'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $projectId = currentProject()->id;

        $unit_types = \App\Models\UnitType::where('project_id', $projectId)
            ->alphabetically()
            ->get();

        $families = \App\Models\Family::where('project_id', $projectId)
            ->alphabetically()
            ->get();

        return inertia('Units/Create', compact('unit_types', 'families'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUnitRequest $request): RedirectResponse
    {
        $unit = Unit::create($request->validated());

        return to_route('units.show', $unit->id)
            ->with('success', __('Unit created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit): Response
    {
        $unit->load('type', 'family');
        $projectId = $unit->project_id;

        $unit_types = \App\Models\UnitType::where('project_id', $projectId)
            ->alphabetically()
            ->get();

        $families = \App\Models\Family::where('project_id', $projectId)
            ->alphabetically()
            ->get();

        return inertia('Units/Edit', compact('unit', 'unit_types', 'families'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('units.show', $unit->id)
            ->with('success', __('Unit updated successfully.'));
    }

    /**
     * Delete the resource.
     */
    public function destroy(Unit $unit): RedirectResponse
    {
        $unit->delete();

        return to_route('units.index')
            ->with('success', __('Unit deleted successfully.'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(Unit $unit): RedirectResponse
    {
        $unit->restore();

        return to_route('units.show', $unit->id)
            ->with('success', __('Unit restored successfully.'));
    }
}
