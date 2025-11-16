<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $units = currentProject()->units()->alphabetically()
            ->with('project', 'type', 'family')
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('Units/Index', [
            'units' => Inertia::deepMerge(fn () => $units->paginate(30)),
        ]);
    }

    public function show(Unit $unit): Response
    {
        return inertia('Units/Show', [
            'unit' => $unit->load('project', 'type', 'family'),
        ]);
    }

    public function create(): Response
    {
        return inertia('Units/Create', [
            'unit_types' => currentProject()->unitTypes()->alphabetically()->get(),
        ]);
    }

    public function store(CreateUnitRequest $request): RedirectResponse
    {
        $unit = currentProject()->units()->save(
            Unit::make($request->validated())
        );

        return to_route('units.show', $unit)
            ->with('success', __('flash.unit_created'));
    }

    public function edit(Unit $unit): Response
    {
        return inertia('Units/Edit', [
            'unit'       => $unit->load('project', 'type', 'family'),
            'unit_types' => currentProject()->unitTypes()->alphabetically()->get(),
        ]);
    }

    public function update(UpdateUnitRequest $request, Unit $unit): RedirectResponse
    {
        $unit->update($request->validated());

        return to_route('units.show', $unit)
            ->with('success', __('flash.unit_updated'));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        Gate::denyIf($unit->family, __('validation.unit_already_assigned'));

        $unit->delete();

        return to_route('units.index')
            ->with('success', __('flash.unit_deleted'));
    }

    public function restore(Unit $unit): RedirectResponse
    {
        $unit->restore();

        return to_route('units.show', $unit)
            ->with('success', __('flash.unit_restored'));
    }
}
