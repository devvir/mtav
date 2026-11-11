<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;

class MinSatisfaction extends TaskRunner
{
    protected Task $task = Task::MIN_SATISFACTION;

    /**
     * Phase 1 solved by GLPK directly: the best rank the worst-placed participant can get (S).
     * Past its time limit it throws GlpkTimeoutException, and `Glpk` searches for S instead
     * (HybridDistribution).
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        $startTime = microtime(true);
        $minSatisfaction = $this->findMinSatisfactionWithGlpk($spec, $timeout);

        return $this->taskResult(
            startTime: $startTime,
            data: ['min_satisfaction' => $minSatisfaction],
            customMetadata: [
                'timeout_ms' => $timeout,
            ]
        );
    }

    /** Writes phase 1's model and data, and reads S from GLPK's objective */
    protected function findMinSatisfactionWithGlpk(LotterySpec $spec, int $timeout): int
    {
        $modFile = $this->files->write('phase1_', '.mod', $this->modelGenerator->generatePhase1Model());
        $datFile = $this->files->write('data_', '.dat', $this->dataGenerator->generateData($spec));

        return $this->runGlpk(
            $timeout,
            $modFile,
            $datFile,
            $this->solutionParser->extractObjective(...)
        );
    }
}
