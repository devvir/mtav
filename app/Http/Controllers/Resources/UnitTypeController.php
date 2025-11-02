<?php

// Copilot - pending review

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitTypeRequest;
use App\Http\Requests\DeleteUnitTypeRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateUnitTypeRequest;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitTypeController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $unitTypes = UnitType::alphabetically()
            ->withCount(['units', 'families'])
            ->where('project_id', $request->project_id ?? currentProject()->id)
            ->get();

        return inertia('UnitTypes/Index', [
            'unit_types' => Inertia::deepMerge(fn () => $unitTypes),
        ]);
    }

    /**
     * Show the resource details.
     */
    public function show(UnitType $unitType): Response
    {
        $unitType->loadCount(['units', 'families']);

        return inertia('UnitTypes/Show', ['unit_type' => $unitType]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return inertia('UnitTypes/Create');
    }

    public function store(CreateUnitTypeRequest $request): RedirectResponse
    {
        $unitType = UnitType::create($request->validated());

        return redirect()->route('unit-types.show', $unitType->id)
            ->with('success', __('Unit type created successfully.'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UnitType $unitType): Response
    {
        return inertia('UnitTypes/Edit', ['unit_type' => $unitType]);
    }

    public function update(UpdateUnitTypeRequest $request, UnitType $unitType): RedirectResponse
    {
        $unitType->update($request->validated());

        return redirect()->route('unit-types.show', $unitType->id)
            ->with('success', __('Unit type updated successfully.'));
    }

    public function destroy(DeleteUnitTypeRequest $_, UnitType $unitType): RedirectResponse
    {
        $unitType->delete();

        return redirect()->back()
            ->with('success', __('Unit type deleted successfully.'));
    }

    /**
     * Restore the soft-deleted resource.
     */
    public function restore(UnitType $unitType): RedirectResponse
    {
        $unitType->restore();

        return redirect()->route('unit-types.index')
            ->with('success', __('Unit type restored successfully.'));
    }
}
