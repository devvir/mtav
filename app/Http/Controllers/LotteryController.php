<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateLotteryPreferencesRequest;
use App\Http\Requests\UpdateLotteryRequest;
use App\Models\Event;
use App\Models\Project;
use App\Services\LotteryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
        $lottery->update($request->validated());

        return back()->with('success', __('flash.lottery_updated'));
    }

    /**
     * Save Family Unit preferences (Members only).
     */
    public function preferences(UpdateLotteryPreferencesRequest $request)
    {
        $family = $request->user()->asMember()->family;

        $this->lotteryService->updatePreferences($family, $request->validated('preferences'));

        return back()->with('success', __('flash.lottery_preferences_updated'));
    }
}
