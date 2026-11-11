<?php

namespace App\Services\Lottery\Contracts;

use App\Models\LotteryRun;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;

/**
 * What a lottery belongs to: it says what a run decides from, and takes what the run decided.
 * The lottery itself knows nothing of who takes part or what is given out; a run's life is the
 * same for every host (`LotteryRuns`).
 */
interface LotteryHost
{
    /** What a run decides from, as the host holds it now (recorded as the run starts) */
    public function manifest(LotteryRun $run): LotteryManifest;

    /** Give each participant what the run assigned them: inside the transaction that completes the run */
    public function assign(LotteryRun $run, ExecutionResult $result): void;

    /** The run completed: whoever is waiting for it is told */
    public function completed(LotteryRun $run): void;

    /** The run was closed as failed: it decided nothing */
    public function failed(LotteryRun $run): void;
}
