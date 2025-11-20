<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\LotteryService;
use App\Services\PlanService;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function __construct(
        private LotteryService $lotteryService,
        private PlanService $planService
    ) {
        // ...
    }

    /**
     * Automatically create a lottery event and plan for each new project.
     */
    public function created(Project $project): void
    {
        $this->lotteryService->createLotteryEvent($project, Auth::user());
        $this->planService->addProject($project);
    }
}
