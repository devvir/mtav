<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Project;
use App\Models\Unit;
use App\Services\PlanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adding (a type may be created on the way; each new unit lands on the plan), changing
 * and removing units: admin tools on the units pages.
 */
class UnitController extends Controller
{
    public function store(UnitRequest $request, Project $project, PlanService $plans): RedirectResponse
    {
        DB::transaction(function () use ($request, $project, $plans) {
            $typeId = $request->validated('new_unit_type')
                ? $project->unitTypes()->create($request->validated('new_unit_type'))->id
                : $request->validated('unit_type_id');

            $unit = $project->units()->create([
                'identifier'   => $request->validated('identifier'),
                'unit_type_id' => $typeId,
            ]);

            $plans->place($unit);
        });

        return back()->with('success', __('flash.unit_created'));
    }

    public function update(UnitRequest $request, Project $project, Unit $unit): RedirectResponse
    {
        $typeChanged = $unit->unit_type_id !== (int) $request->validated('unit_type_id');

        if ($typeChanged && $project->lotteryLocked()) {
            throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
        }

        $unit->update($request->safe()->only('identifier', 'unit_type_id'));

        return back()->with('success', __('flash.unit_updated'));
    }

    public function destroy(Project $project, Unit $unit): RedirectResponse
    {
        if ($unit->family_id) {
            throw ValidationException::withMessages(['unit' => __('validation.unit_assigned')]);
        }

        $unit->delete();

        return to_route('units.index')->with('success', __('flash.unit_deleted'));
    }
}
