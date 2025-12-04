<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\LotteryOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Picks up lottery execution event and delegates to orchestrator.
 */
class ExecuteLotteryListener implements ShouldQueue
{
    public function handle(LotteryExecutionTriggered $event): void
    {
        Log::info('ExecuteLotteryListener handling LotteryExecutionTriggered event.');

        app()->makeWith(LotteryOrchestrator::class, [
            'solver'   => $this->makeSolver(),
            'manifest' => $event->manifest,
        ])->execute();
    }

    /**
     * Resolve the solver from the IoC container, based on the lottery config file.
     */
    protected function makeSolver(): SolverInterface
    {
        $selected = config('lottery.default');
        $solverClass = config("lottery.solvers.{$selected}.solver");

        if (! $solverClass) {
            throw new RuntimeException("Lottery solver [{$selected}] not found.");
        }

        return app()->makeWith($solverClass);
    }
}
