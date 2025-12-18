<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\FeasibilityResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Tasks;
use Generator;
use InvalidArgumentException;

class MinSatisfaction extends TaskRunner
{
    /**
     * Find minimum satisfaction using GLPK optimization.
     *
     * Returns optimal S or throws GlpkTimeoutException.
     * On timeout, caller should use binarySearchGenerator() for fallback.
     */
    public function execute(LotterySpec $spec, float $timeout, array $context = []): TaskResult
    {
        $startTime = microtime(true);
        $minSatisfaction = $this->findMinSatisfactionWithGlpk($spec, $timeout);
        $elapsedMs = (microtime(true) - $startTime) * 1000;

        return $this->buildResult($startTime, [
            'strategy'         => 'glpk',
            'min_satisfaction' => $minSatisfaction,
            'glpk_time_ms'     => $elapsedMs,
            'glpk_timeout_ms'  => $timeout,
        ]);
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

        return $this->buildResult($startTime, [
            'strategy'             => 'binsearch',
            'min_satisfaction'     => $lo,
            'binsearch_iterations' => $iterations,
            'binsearch_time_ms'    => (microtime(true) - $startTime) * 1000,
        ]);
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

    /**
     * Build TaskResult from result data.
     */
    protected function buildResult(float $startTime, array $result): TaskResult
    {
        return new TaskResult(
            task: Tasks::MIN_SATISFACTION,
            data: ['min_satisfaction' => $result['min_satisfaction']],
            metadata: $this->buildMetadata($startTime, $result),
        );
    }
}
