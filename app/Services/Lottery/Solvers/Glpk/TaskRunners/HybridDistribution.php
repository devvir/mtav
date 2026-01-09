<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\FeasibilityResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use Generator;
use InvalidArgumentException;

class HybridDistribution extends TaskRunner
{
    protected Task $task = Task::HYBRID_DISTRIBUTION;

    /**
     * Execute hybrid distribution task (phase1 binary search + phase2 glpk).
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        // Sum of step timeouts tends to $timeout * 2 (worst case, highly unlikely).
        $iterations = (int) ceil(log($spec->familyCount(), 2)) + 1;
        $stepTimeout = 2 * $timeout / $iterations;

        $generator = $this->binarySearchGenerator($spec);
        $taskResults = $this->searchBestDistribution($generator, $spec, $stepTimeout);

        $startTime = microtime(true);

        if (empty($taskResults)) {
            throw (new GlpkInfeasibleException(
                'Binary search completed without finding any feasible solution'
            ))->with([
                'spec'       => $spec,
                'iterations' => $iterations,
            ]);
        }

        return $this->mergeTaskResults($startTime, $stepTimeout, $iterations, $taskResults);
    }

    /**
     * Search for best distribution using binary search generator with GLPK phase2 feedback.
     *
     * @return TaskResult[]
     */
    protected function searchBestDistribution(Generator $generator, LotterySpec $spec, float $timeout): array
    {
        $distributionRunner = app(TaskRunnerFactory::class)->make(Task::UNIT_DISTRIBUTION);

        $taskResults = [];

        while ($generator->valid()) {
            try {
                $taskResults[] = $distributionRunner
                    ->withContext([ 'min_satisfaction' => $generator->current() ])
                    ->execute($spec, $timeout);

                $generator->send(FeasibilityResult::FEASIBLE);
            } catch (GlpkInfeasibleException) {
                $generator->send(FeasibilityResult::INFEASIBLE);
            }
        }

        return $taskResults;
    }

    /**
     * Binary search generator for minimum feasible S.
     *
     * Yields candidate S values with state tracking. Picks next candidate based on
     * feedback from caller (whether last candidate lead to feasible/infeasible phase2).
     *
     * @return Generator<int, int, FeasibilityResult, int>  Returns number of iterations on completion.
     */
    protected function binarySearchGenerator(LotterySpec $spec): Generator
    {
        $lo = 1;
        $hi = count($spec->units);

        while ($lo <= $hi) {
            $candidateS = (int) floor(($lo + $hi) / 2);

            // Yield candidate and wait for feedback (feasible/infeasible)
            $feedback = yield $candidateS;

            match ($feedback) {
                FeasibilityResult::FEASIBLE   => $hi = $candidateS - 1,
                FeasibilityResult::INFEASIBLE => $lo = $candidateS + 1,

                default => throw new InvalidArgumentException("Invalid feedback: " . ($feedback ?? 'null')),
            };
        }
    }

    /**
     * Generate a final TaskResult containing all intermediate results' data.
     */
    protected function mergeTaskResults(float $startTime, float $timeout, int $iterations, array $taskResults): TaskResult
    {
        $finalResult = end($taskResults);

        return $this->taskResult(
            startTime: $startTime,
            data: [
                'distribution'     => $finalResult->get('distribution'),
                'min_satisfaction' => $finalResult->metadata['min_satisfaction'],
            ],
            customMetadata: [
                'iterations'      => $iterations,
                'step_timeout_ms' => $timeout * 1000,
                'timeout_ms'      => $timeout * $iterations * 1000,
                'feasible_steps'  => array_map(fn ($result) => $this->phase2ResultDigest($result), $taskResults),
                'artifacts'       => $finalResult->metadata['artifacts'],
            ],
        );
    }

    protected function phase2ResultDigest(TaskResult $result): array
    {
        return [
            'distribution'     => $result->get('distribution'),
            'min_satisfaction' => $result->metadata['min_satisfaction'],
            'time_ms'          => $result->metadata['time_ms'],
        ];
    }
}
