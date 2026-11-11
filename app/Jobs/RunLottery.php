<?php

namespace App\Jobs;

use App\Enums\LotteryRunStatus;
use App\Models\LotteryRun;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\LotteryRuns;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

/**
 * Solves a lottery run in the background, then writes its assignment (or records
 * why it failed). Solving may take seconds; nothing else happens in the request.
 * It solves what the run recorded when it started (its manifest), never what the
 * project holds by the time it runs: the record and its fingerprint say what was solved.
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

    public function handle(LotteryRuns $runs, AuditService $audits): void
    {
        $run = $this->run->fresh();

        if ($run?->status !== LotteryRunStatus::RUNNING) {
            return;
        }

        try {
            $manifest = $audits->manifestOf($run)
                ?? throw new RuntimeException("Lottery run [{$run->id}] recorded nothing to decide from.");

            $result = app(LotteryOrchestrator::class, ['solver' => $this->solver($run)])->execute($manifest);

            $runs->solved($run, $result);
        } catch (Throwable $e) {
            report($e);

            $reason = $e instanceof LotteryExecutionException
                ? [$e->getUserMessageKey(), $e->getUserMessageParams()]
                : ['lottery.execution_failed', []];

            $runs->fail($run, ...$reason, debug: ['exception' => $e::class, 'error' => $e->getMessage()]);
        }
    }

    /**
     * The job died without finishing (timed out, or its worker was stopped): the run fails,
     * so the lottery can run again instead of staying locked
     */
    public function failed(?Throwable $e): void
    {
        app(LotteryRuns::class)->fail($this->run, 'lottery.execution_failed', debug: [
            'exception' => $e ? $e::class : null,
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
