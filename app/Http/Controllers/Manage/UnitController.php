<?php

namespace App\Http\Controllers\Manage;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Http\Resources\PlanResource;
use App\Models\Project;
use App\Models\Unit;
use App\Services\NotificationService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

/**
 * Adding (a type may be created on the way; each new unit lands on the plan), changing
 * and removing units: admin tools on the units pages.
 */
class UnitController extends Controller
{
    /**
     * A new unit, on the plan where it was drawn (answered with its shape, to the plan
     * editor), else next to the others.
     */
    public function store(UnitRequest $request, Project $project, PlanService $plans, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        // With the project held still: a run starting meanwhile has this unit, or it is refused
        $item = $project->holdingLottery(function () use ($request, $project, $plans) {
            $project->ensureLotteryNotRunning();

            $typeId = $request->validated('new_unit_type')
                ? $project->unitTypes()->create($request->validated('new_unit_type'))->id
                : $request->validated('unit_type_id');

            $unit = $project->units()->create([
                'identifier'   => $request->validated('identifier'),
                'unit_type_id' => $typeId,
            ]);

            return $plans->place($unit, $request->validated('polygon'), (int) $request->validated('floor'));
        });

        $notifications->bundle($project, NotificationType::UNITS_ADDED, 'units_added', $request->user(), route('units.index', $project));

        if ($request->wantsJson()) {
            return response()->json(['item' => PlanResource::item($item->load('unit.family'))], 201);
        }

        return to_route('units.index')->with('success', __('flash.unit_created'));
    }

    public function update(UnitRequest $request, Project $project, Unit $unit): RedirectResponse
    {
        $project->holdingLottery(function () use ($request, $project, $unit) {
            $typeChanged = $unit->unit_type_id !== (int) $request->validated('unit_type_id');

            if ($typeChanged && $project->lotteryLocked()) {
                throw ValidationException::withMessages(['unit_type_id' => __('lottery.locked')]);
            }

            $unit->update($request->safe()->only('identifier', 'unit_type_id'));
        });

        return to_route('units.index', ['unit' => $unit->id])->with('success', __('flash.unit_updated'));
    }

    public function destroy(Project $project, Unit $unit): RedirectResponse
    {
        $project->holdingLottery(function () use ($project, $unit) {
            $project->ensureLotteryNotRunning();

            if ($unit->fresh()?->family_id) {
                throw ValidationException::withMessages(['unit' => __('validation.unit_assigned')]);
            }

            $unit->delete();
        });

        return to_route('units.index')->with('success', __('flash.unit_deleted'));
    }
}
