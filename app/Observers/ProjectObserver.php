<?php

namespace App\Observers;

use App\Models\Project;
use App\Services\LotteryService;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function __construct(
        private LotteryService $lotteryService
    ) {
        // ...
    }

    /**
     * Automatically create a lottery event for each new project.
     */
    public function created(Project $project): void
    {
        $this->lotteryService->createEvent($project, Auth::user());
    }
}
