<?php

namespace App\Http\Controllers\Manage;

use App\Enums\LotteryRunStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Http\Resources\LotteryRunResource;
use App\Models\LotteryRun;
use App\Models\Project;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\LotteryRequiresConfirmationException;
use App\Services\LotteryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Running the lottery: schedule it, check readiness, run it, and (exceptionally)
 * invalidate a run, with a reason everyone in the project reads.
 */
class LotteryController extends Controller
{
    public function __construct(private LotteryService $lottery)
    {
    }

    public function show(Request $request, Project $project): Response
    {
        $activeRun = $project->activeLotteryRun;

        return inertia('Manage/Lottery', [
            'lottery'   => EventResource::make($project->lottery),
            'status'    => $activeRun?->status->value,
            'readiness' => $project->unitTypes()->withCount('units', 'families')->alphabetically()->get()
                ->map(fn ($type) => $type->only('id', 'name', 'units_count', 'families_count')),
            'runs' => LotteryRunResource::collection(
                $project->lotteryRuns()->with('starter', 'invalidator')->latest('id')->get()
            ),
            'audits'  => fn () => $activeRun?->audits()->get(['id', 'type', 'audit', 'created_at']),
            'confirm' => $request->session()->get('confirm'),
        ]);
    }

    /**
     * Its date and description (the title is fixed), until it runs.
     */
    public function schedule(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'between:2,5000'],
            'start_date'  => ['nullable', 'date'],
        ]);

        try {
            $this->lottery->schedule($project->lottery, $data);
        } catch (LotteryExecutionException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', __('flash.lottery_scheduled'));
    }

    public function run(Request $request, Project $project): RedirectResponse
    {
        $options = $request->validate(['options' => ['array'], 'options.*' => ['string']])['options'] ?? [];

        try {
            $this->lottery->run($project, $request->user(), $options);
        } catch (LotteryRequiresConfirmationException $e) {
            return back()->with('confirm', ['option' => $e->getOption(), 'message' => $e->getUserMessage()]);
        } catch (LotteryExecutionException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', __('flash.lottery_started'));
    }

    public function invalidate(Request $request, Project $project, LotteryRun $lotteryRun): RedirectResponse
    {
        abort_unless($lotteryRun->status === LotteryRunStatus::COMPLETED, 409);

        $reason = $request->validate(['reason' => ['required', 'string', 'between:10,1000']])['reason'];

        $this->lottery->invalidate($lotteryRun, $request->user(), $reason);

        return back()->with('success', __('flash.lottery_invalidated'));
    }
}
