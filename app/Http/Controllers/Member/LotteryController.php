<?php

namespace App\Http\Controllers\Member;

use App\Enums\LotteryRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\LotteryRunResource;
use App\Http\Resources\PlanResource;
use App\Http\Resources\UnitResource;
use App\Models\Project;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\PreferencesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The lottery as people in the project live it: when it happens, their family's
 * ranking, the outcome, and every run (invalidated ones with their reason).
 */
class LotteryController extends Controller
{
    public function __construct(private PreferencesService $preferences)
    {
    }

    public function show(Request $request, Project $project): Response
    {
        $user = $request->user();
        $family = $user->familyIn($project);
        $activeRun = $project->activeLotteryRun;

        return inertia('Member/Lottery', [
            'lottery' => EventResource::make($project->lottery),
            'status'  => $activeRun?->status->value,
            /** A run under way: when it began and when it ends, for its progress */
            'pace' => $activeRun?->status === LotteryRunStatus::RUNNING
                ? ['started_at' => $activeRun->started_at, 'reveal_at' => $activeRun->reveal_at]
                : null,
            'runs' => LotteryRunResource::collection(
                $project->lotteryRuns()->with('starter', 'invalidator')->latest('id')->get()
            ),
            /** Families see only their own ranking */
            'family' => $family ? [
                'id'           => $family->id,
                'name'         => $family->name,
                'unit_type'    => $family->unitType->name,
                'unit_type_id' => $family->unit_type_id,
            ] : null,
            /** Where the family's possible units are: after the page, it is not needed to rank */
            'plan'    => Inertia::defer(fn () => PlanResource::make($project->plan->load('items.unit.family'))),
            'ranking' => $family ? UnitResource::collection($this->preferences->ranking($family)) : null,
            /** Once decided, everyone sees which unit each family received */
            'assignment' => $activeRun?->status === LotteryRunStatus::COMPLETED
                ? $project->families()->with('unit:id,family_id,identifier')->alphabetically()->get()
                    ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name, 'unit' => $f->unit?->only('id', 'identifier')])
                : null,
            'canRank' => $user->can('rankUnits', $project) && ! $activeRun,
            // Admins run it from here: its readiness, every run's record, the server's questions
            'admin' => $user->can('update', $project) ? [
                'readiness' => $project->unitTypes()->withCount('units', 'families')->alphabetically()->get()
                    ->map(fn ($type) => $type->only('id', 'name', 'units_count', 'families_count')),
                'runs' => LotteryRunResource::collection(
                    $project->lotteryRuns()->with('starter', 'invalidator')->latest('id')->get()
                ),
                'confirm' => $request->session()->get('confirm'),
            ] : null,
        ]);
    }

    /**
     * Any member of a family ranks the units of its type for the whole family.
     */
    public function rank(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('rankUnits', $project);

        $units = $request->validate([
            'preferences'   => ['present', 'array'],
            'preferences.*' => ['integer'],
        ])['preferences'];

        try {
            $this->preferences->rank($request->user()->familyIn($project), $units);
        } catch (LotteryExecutionException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back();
    }
}
