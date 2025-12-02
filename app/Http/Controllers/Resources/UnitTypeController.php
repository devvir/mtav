<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitTypeRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateUnitTypeRequest;
use App\Models\UnitType;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitTypeController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $unitTypes = currentProject()->unitTypes()->alphabetically()
            ->withCount('units', 'families')
            ->when($request->q, fn ($q, $search) => $q->search($search));

        return inertia('UnitTypes/Index', [
            'unit_types' => Inertia::deepMerge(fn () => $unitTypes->paginate(30)),
        ]);
    }

    public function show(UnitType $unitType): Response
    {
        return inertia('UnitTypes/Show', [
            'unit_type' => $unitType->load('units', 'families'),
        ]);
    }

    public function create(): Response
    {
        $formSpecs = FormService::make(UnitType::class, FormType::CREATE);

        return inertia('UnitTypes/Create', [
            'form' => $formSpecs,
        ]);
    }

    public function store(CreateUnitTypeRequest $request): RedirectResponse
    {
        $unitType = currentProject()->units()->save(
            UnitType::make($request->validated())
        );

        return to_route('unit_types.show', $unitType)
            ->with('success', __('flash.unit_type_created'));
    }

    public function edit(UnitType $unitType): Response
    {
        $formSpecs = FormService::make($unitType, FormType::UPDATE);

        return inertia('UnitTypes/Edit', [
            'form' => $formSpecs,
        ]);
    }

    public function update(UpdateUnitTypeRequest $request, UnitType $unitType): RedirectResponse
    {
        $unitType->update($request->validated());

        return to_route('unit_types.show', $unitType)
            ->with('success', __('flash.unit_type_updated'));
    }

    public function destroy(UnitType $unitType): RedirectResponse
    {
        $inUse = $unitType->families()->count() || $unitType->units()->count();

        Gate::denyIf($inUse, __('validation.unit_type_has_dependencies'));

        $unitType->delete();

        return to_route('unit_types.index')
            ->with('success', __('flash.unit_type_deleted'));
    }

    public function restore(UnitType $unitType): RedirectResponse
    {
        $unitType->units()->withTrashed()->restore();
        $unitType->restore();

        return to_route('unit_types.show', $unitType)
            ->with('success', __('flash.unit_type_restored'));
    }
}
