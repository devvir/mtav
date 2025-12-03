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
        Log::info('ExecuteLotteryListener handling LotteryExecutionTriggered event.', [
            'manifest' => $event->manifest,
        ]);

        $solver = $this->fetchSolver();

        $orchestrator = LotteryOrchestrator::make($solver, $event->manifest);
        $orchestrator->execute();
    }

    /**
     * Resolve the solver from the IoC container, based on lottery configuration.
     */
    protected function fetchSolver(): SolverInterface
    {
        $default = config('lottery.default');
        $solverConfig = config("lottery.solvers.{$default}");

        if (! $solverConfig) {
            throw new RuntimeException("Lottery solver [{$default}] not found in configuration.");
        }

        $solverClass = $solverConfig['solver'];
        $config = $solverConfig['config'] ?? [];

        return app()->makeWith($solverClass, ['config' => $config]);
    }
}
