<?php

namespace App\Jobs;

use App\Enums\LotteryRunStatus;
use App\Models\LotteryRun;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\LotteryRuns;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * A lottery run's assignment, found already, written when the run is due to end (its
 * reveal_at): until then everyone sees the lottery running.
 */
class PublishLotteryRun implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public LotteryRun $run)
    {
        $this->afterCommit();
    }

    public function handle(LotteryRuns $runs): void
    {
        $run = $this->run->fresh();

        if ($run?->status !== LotteryRunStatus::RUNNING || ! $run->result) {
            return;
        }

        $runs->complete($run, new ExecutionResult($run->result['picks'], $run->result['orphans']));
    }

    /** Never left running: a run whose result cannot be written fails, and can run again */
    public function failed(?Throwable $e): void
    {
        app(LotteryRuns::class)->fail($this->run, 'lottery.execution_failed', debug: [
            'exception' => $e ? $e::class : null,
            'error'     => $e?->getMessage(),
        ]);
    }
}
