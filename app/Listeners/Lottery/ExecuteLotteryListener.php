<?php

namespace App\Listeners\Lottery;

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Services\Lottery\Contracts\ExecutorInterface;
use App\Services\Lottery\LotteryOrchestrator;
use RuntimeException;

/**
 * Picks up lottery execution event and delegates to orchestrator.
 */
class ExecuteLotteryListener
{
    public function handle(LotteryExecutionTriggered $event): void
    {
        $executor = $this->resolveExecutor();

        $orchestrator = LotteryOrchestrator::make($executor, $event->manifest);
        $orchestrator->execute();
    }

    /**
     * Resolve the executor from configuration.
     */
    protected function resolveExecutor(): ExecutorInterface
    {
        $default = config('lottery.default');
        $executorConfig = config("lottery.executors.{$default}");

        if (! $executorConfig) {
            throw new RuntimeException(
                "Lottery executor [{$default}] not found in configuration."
            );
        }

        $executorClass = $executorConfig['executor'];
        $config = $executorConfig['config'] ?? [];

        return app()->makeWith($executorClass, ['config' => $config]);
    }
}
