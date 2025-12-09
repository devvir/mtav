<?php

namespace App\Http\Controllers\Resources;

use App\Http\Requests\CreateUnitRequest;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Unit;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $units = currentProject()->units()
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
        $formSpecs = FormService::make(Unit::class, FormType::CREATE);

        return inertia('Units/Create', [
            'form' => $formSpecs,
        ]);
    }

    public function store(CreateUnitRequest $request): RedirectResponse
    {
        $unit = DB::transaction(function () use ($request) {
            if ($request->new_type_name && $request->new_type_description) {
                $unitType = currentProject()->unitTypes()->create([
                    'name'        => $request->new_type_name,
                    'description' => $request->new_type_description,
                ]);
            }

            return currentProject()->units()->create([
                ...Arr::only($request->validated(), ['project_id', 'identifier']),
                'unit_type_id' => $unitType->id ?? $request->unit_type_id,
            ]);
        });

        return to_route('units.show', $unit)->with('success', __('flash.unit_created'));
    }

    public function edit(Unit $unit): Response
    {
        $formSpecs = FormService::make($unit, FormType::UPDATE);

        return inertia('Units/Edit', [
            'form' => $formSpecs,
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
