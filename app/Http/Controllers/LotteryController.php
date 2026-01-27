<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExecuteLotteryRequest;
use App\Http\Requests\InvalidateLotteryRequest;
use App\Http\Requests\ProjectScopedRequest;
use App\Http\Requests\UpdateLotteryPreferencesRequest;
use App\Http\Requests\UpdateLotteryRequest;
use App\Models\Event;
use App\Models\Project;
use App\Services\Lottery\Exceptions\LockedLotteryException;
use App\Services\Lottery\Exceptions\LockedLotteryPreferencesException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\LotteryRequiresConfirmationException;
use App\Services\LotteryService;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LotteryController
{
    public function __construct(private LotteryService $lotteryService)
    {
        // ...
    }

    /**
     * Display the lottery management page.
     */
    public function index(ProjectScopedRequest $request): Response
    {
        $project = Project::find($request->project_id);
        $family = $request->user()->asMember()?->family;

        $preferences = $family ? $this->lotteryService->preferences($family) : [];

        return Inertia::render('Lottery', [
            'plan'     => $project->plan,
            'lottery'  => $project->lottery()->withTrashed()->with('audits')->first(),
            'families' => $project->families()->alphabetically()->with('unit')->get(),

            /** For Members only (preferences picker) */
            'preferences' => fn () => $preferences,

            /** For Admins only (lottery execution options) */
            'options' => $request->session()->get('options', []),
            'warning' => $request->session()->get('warning', null),
        ]);
    }

    /**
     * Update Lottery details (Admins only).
     */
    public function update(UpdateLotteryRequest $request, Event $lottery)
    {
        try {
            $this->lotteryService->updateLotteryEvent($lottery, $request->validated());
        } catch (LockedLotteryException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back()->with('success', __('lottery.lottery_updated'));
    }

    /**
     * Save Family Unit preferences (Members only).
     */
    public function preferences(UpdateLotteryPreferencesRequest $request)
    {
        $family = $request->user()->asMember()->family;

        try {
            $this->lotteryService->updatePreferences($family, $request->validated('preferences'));
        } catch (LockedLotteryPreferencesException $e) {
            return back()->with('error', $e->getUserMessage());
        }

        return back();
    }

    /**
     * Execute the lottery (Admins only).
     */
    public function execute(ExecuteLotteryRequest $request, Event $lottery)
    {
        $options = $request->input('options', []);

        try {
            $this->lotteryService->execute($lottery, $options);

        } catch (LotteryRequiresConfirmationException $e) {
            return back()->with([
                'options' => [...$options, $e->getOption()],
                'warning' => $e->getUserMessage(),
            ]);

        } catch (LotteryExecutionException $e) {
            Log::error($e->getMessage());
            return back()->with('error', $e->getUserMessage());

        } catch (Throwable $e) {
            report($e);
            return back()->with('error', __('lottery.execution_failed'));
        }

        return back(); // UI provides RT feedback, no flash message needed
    }

    /**
     * Invalidate a partial or completed Lottery execution (SuperAdmins only).
     */
    public function invalidate(InvalidateLotteryRequest $_, Event $lottery)
    {
        try {
            $this->lotteryService->invalidate($lottery);
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', __('lottery.invalidation_failed'));
        }

        return back()->with('success', __('lottery.invalidated_successfully'));
    }
}
