<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\UnitResource;
use App\Http\Resources\UnitTypeResource;
use App\Models\Project;
use App\Models\Unit;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request, Project $project): Response
    {
        $units = $project->units()
            ->with('type:id,name', 'family:id,name')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->when($request->integer('unit_type'), fn ($q, $typeId) => $q->where('unit_type_id', $typeId))
            ->alphabetically()
            ->paginate(50)
            ->withQueryString();

        return inertia('Manage/Units/Index', [
            'units'     => UnitResource::collection($units),
            'unitTypes' => $project->unitTypes()->alphabetically()->get(['id', 'name']),
            'filters'   => ['q' => $request->q, 'unit_type' => $request->integer('unit_type') ?: null],
        ]);
    }

    public function create(Request $request, Project $project): Response
    {

        return inertia('Manage/Units/Create', [
            'unitTypes' => UnitTypeResource::collection($project->unitTypes()->alphabetically()->get()),
            'unitType'  => $request->integer('unit_type') ?: null,
        ]);
    }

    public function store(UnitRequest $request, Project $project, PlanService $plans): RedirectResponse
    {

        $unit = DB::transaction(function () use ($request, $project, $plans) {
            $typeId = $request->validated('new_unit_type')
                ? $project->unitTypes()->create($request->validated('new_unit_type'))->id
                : $request->validated('unit_type_id');

            $unit = $project->units()->create([
                'identifier'   => $request->validated('identifier'),
                'unit_type_id' => $typeId,
            ]);

            $plans->place($unit);

            return $unit;
        });

        return $request->boolean('another')
            ? to_route('manage.units.create', ['unit_type' => $unit->unit_type_id])->with('success', __('flash.unit_created'))
            : to_route('manage.units.index')->with('success', __('flash.unit_created'));
    }

    public function edit(Project $project, Unit $unit): Response
    {

        return inertia('Manage/Units/Edit', [
            'unit'      => UnitResource::make($unit->load('type')),
            'unitTypes' => UnitTypeResource::collection($project->unitTypes()->alphabetically()->get()),
        ]);
    }

    public function update(UnitRequest $request, Project $project, Unit $unit): RedirectResponse
    {

        $typeChanged = $unit->unit_type_id !== (int) $request->validated('unit_type_id');

        if ($typeChanged && $project->lotteryLocked()) {
            throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
        }

        $unit->update($request->safe()->only('identifier', 'unit_type_id'));

        return to_route('manage.units.index')->with('success', __('flash.unit_updated'));
    }

    public function destroy(Project $project, Unit $unit): RedirectResponse
    {

        if ($unit->family_id) {
            throw ValidationException::withMessages(['unit' => __('validation.unit_assigned')]);
        }

        $unit->delete();

        return to_route('manage.units.index')->with('success', __('flash.unit_deleted'));
    }
}
