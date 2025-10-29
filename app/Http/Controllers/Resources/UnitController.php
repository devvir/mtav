<?php

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
            ->when($request->project_id, fn ($q, $projectId) => $q->where('project_id', $projectId))
            ->when($request->q, fn ($query, $q) => $query->whereLike('number', "%$q%"));

        return inertia('Units/Index', [
            'units' => Inertia::deepMerge(fn () => $units->paginate(30)),
            'q' => $request->q ?? '',
        ]);
    }

    /**
     * Show the resource details.
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
        $unit = Unit::create($request->all());

        return to_route('units.show', $unit->id)
            ->with('success', __('Unit created successfully.'));
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
        $unit->update($request->all());

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
