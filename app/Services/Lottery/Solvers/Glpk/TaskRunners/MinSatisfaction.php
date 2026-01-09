<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\FeasibilityResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use Generator;
use InvalidArgumentException;

class MinSatisfaction extends TaskRunner
{
    protected Task $task = Task::MIN_SATISFACTION;

    /**
     * Find minimum satisfaction using GLPK optimization.
     *
     * Returns optimal S or throws GlpkTimeoutException.
     * On timeout, caller should use binarySearchGenerator() for fallback.
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

    /**
     * Binary search generator for minimum feasible S.
     *
     * Yields TaskResult candidates with state tracking.
     * Caller validates each candidate and sends feedback via generator->send().
     *
     * @return Generator<int, TaskResult, FeasibilityResult, TaskResult>
     */
    public function binarySearchGenerator(LotterySpec $spec): Generator
    {
        $iterations = 0;
        $startTime = microtime(true);

        $lo = 1;
        $hi = count($spec->units);

        while ($lo <= $hi) {
            $iterations++;
            $candidateS = (int) floor(($lo + $hi) / 2);

            // Yield candidate and wait for feedback (feasible/infeasible)
            $feedback = yield $candidateS;

            match ($feedback) {
                FeasibilityResult::FEASIBLE   => $hi = $candidateS - 1,
                FeasibilityResult::INFEASIBLE => $lo = $candidateS + 1,

                default => throw new InvalidArgumentException("Invalid feedback: " . ($feedback ?? 'null')),
            };
        }

        return $this->taskResult(
            startTime: $startTime,
            data: ['min_satisfaction' => $lo],
            customMetadata: [
                'iterations' => $iterations,
            ],
        );
    }

    /**
     * Run GLPK optimization to find minimum S.
     */
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
