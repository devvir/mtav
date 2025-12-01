<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExecuteLotteryRequest;
use App\Http\Requests\UpdateLotteryPreferencesRequest;
use App\Http\Requests\UpdateLotteryRequest;
use App\Models\Event;
use App\Models\Project;
use App\Services\Lottery\Exceptions\LockedLotteryException;
use App\Services\Lottery\Exceptions\LockedLotteryPreferencesException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\UnitFamilyMismatchException;
use App\Services\LotteryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LotteryController extends Controller
{
    public function __construct(
        private LotteryService $lotteryService
    ) {
    }

    /**
     * Display the lottery management page.
     */
    public function index(Request $request): Response
    {
        $family = $request->user()->asMember()?->family;
        $preferences = $family ? $this->lotteryService->preferences($family) : [];

        return Inertia::render('Lottery/Index', [
            'lottery' => Project::current()->lottery,
            'units'   => $preferences, /** For Members only */
            'plan'    => currentProject()->plan,
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

        return back()->with('success', __('flash.lottery_updated'));
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

        return back()->with('success', __('flash.lottery_preferences_updated'));
    }

    /**
     * Execute the lottery (Admins only).
     */
    public function execute(ExecuteLotteryRequest $request, Event $lottery)
    {
        $overrideCountMismatch = $request->boolean('override_count_mismatch', false);

        try {
            $this->lotteryService->execute($lottery, $overrideCountMismatch);
        } catch (UnitFamilyMismatchException $e) {
            // Return custom mismatchError prop to allow retries (forced, bypass this check)
            return back()->with('mismatchError', $e->getUserMessage());
        } catch (LotteryExecutionException $e) {
            return back()->with('error', $e->getUserMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', __('lottery.execution_failed'));
        }

        return back()->with('success', __('flash.lottery_executed_successfully'));
    }
}
