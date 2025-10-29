<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitTypeRequest;
use App\Http\Requests\DeleteUnitTypeRequest;
use App\Http\Requests\IndexUnitTypesRequest;
use App\Http\Requests\UpdateUnitTypeRequest;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UnitTypeController extends Controller
{
    public function index(IndexUnitTypesRequest $request): Response
    {
        $unitTypes = UnitType::alphabetically()
            ->withCount(['units', 'families'])
            ->when($request->project_id, fn ($q, $projectId) => $q->where('project_id', $projectId))
            ->when($request->q, fn ($query, $q) => $query->whereLike('name', "%$q%"));

        return inertia('UnitTypes/Index', [
            'unit_types' => Inertia::deepMerge(fn () => $unitTypes->paginate(30)),
            'q' => $request->q ?? '',
        ]);
    }

    public function store(CreateUnitTypeRequest $request): RedirectResponse
    {
        UnitType::create($request->all());

        return redirect()->route('unit-types.index')
            ->with('success', __('Unit type created successfully.'));
    }

    public function update(UpdateUnitTypeRequest $request, UnitType $unitType): RedirectResponse
    {
        $unitType->update($request->all());

        return redirect()->route('unit-types.index')
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
