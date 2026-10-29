<?php

namespace App\Jobs;

use App\Enums\LotteryRunStatus;
use App\Models\LotteryRun;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\LotteryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Solves a lottery run in the background, then writes its assignment (or records
 * why it failed). Solving may take seconds; nothing else happens in the request.
 */
class RunLottery implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * Long enough for every type's solve (each bounded by the solver's own timeout); past it,
     * the run fails rather than stay running. The queue's retry_after is longer still.
     */
    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public function __construct(public LotteryRun $run)
    {
        $this->afterCommit();
    }

    public function handle(LotteryService $lottery): void
    {
        $run = $this->run->fresh();

        if ($run?->status !== LotteryRunStatus::RUNNING) {
            return;
        }

        try {
            $result = app(LotteryOrchestrator::class, ['solver' => $this->solver($run)])
                ->execute($lottery->manifest($run));

            $lottery->complete($run, $result);
        } catch (Throwable $e) {
            report($e);

            $lottery->fail(
                $run,
                $e instanceof LotteryExecutionException ? $e->getUserMessage() : __('lottery.execution_failed'),
                ['exception' => get_class($e), 'error' => $e->getMessage()],
            );
        }
    }

    /**
     * The job died without finishing (timed out, or its worker was stopped): the run fails,
     * so the lottery can run again instead of staying locked
     */
    public function failed(?Throwable $e): void
    {
        app(LotteryService::class)->fail($this->run, __('lottery.execution_failed'), [
            'exception' => $e ? get_class($e) : null,
            'error'     => $e?->getMessage(),
        ]);
    }

    private function solver(LotteryRun $run): SolverInterface
    {
        $class = config("lottery.solvers.{$run->solver}.solver")
            ?? throw new RuntimeException("Lottery solver [{$run->solver}] is not configured.");

        return app($class);
    }
}
