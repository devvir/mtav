<?php

namespace App\Services\Lottery\Contracts;

use App\Models\LotteryRun;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;

/**
 * What a lottery belongs to: it says what a run decides from, and takes what the run decided.
 * The lottery itself knows nothing of who takes part or what is given out.
 */
interface LotteryHost
{
    public function manifest(LotteryRun $run): LotteryManifest;

    /** Solved: the result may wait for the run's reveal */
    public function solved(LotteryRun $run, ExecutionResult $result): void;

    public function complete(LotteryRun $run, ExecutionResult $result): void;

    public function fail(LotteryRun $run, string $messageKey, array $messageParams = [], array $debug = []): void;
}
