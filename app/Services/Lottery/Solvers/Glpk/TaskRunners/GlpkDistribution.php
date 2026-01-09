<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;

class GlpkDistribution extends TaskRunner
{
    protected Task $task = Task::GLPK_DISTRIBUTION;

    /**
     * Execute two-phase GLPK distribution (phase1 glpk + phase2 glpk).
     *
     * Phase 1: Find minimum satisfaction using GLPK optimization
     * Phase 2: Distribute units using GLPK with S constraint from Phase 1
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        $startTime = microtime(true);

        $phase1Result = $this->executePhase1($spec, $this->context['phase1_timeout'] ?? $timeout);
        $phase2Result = $this->executePhase2($spec, $timeout, $phase1Result->get('min_satisfaction'));

        return $this->mergeTaskResults($startTime, $phase1Result, $phase2Result);
    }

    /**
     * Execute Phase 1: Find minimum satisfaction using GLPK.
     */
    protected function executePhase1(LotterySpec $spec, float $timeout): TaskResult
    {
        $minSatisfactionRunner = app(TaskRunnerFactory::class)->make(Task::MIN_SATISFACTION);

        return $minSatisfactionRunner->execute($spec, $timeout);
    }

    /**
     * Execute Phase 2: Distribute units with minimum satisfaction constraint.
     */
    protected function executePhase2(LotterySpec $spec, float $timeout, int $minSatisfaction): TaskResult
    {
        $runner = app(TaskRunnerFactory::class)
            ->make(Task::UNIT_DISTRIBUTION)
            ->withContext([ 'min_satisfaction' => $minSatisfaction ]);

        return $runner->execute($spec, $timeout);
    }

    /**
     * Generate final TaskResult containing both phase results.
     */
    protected function mergeTaskResults(float $startTime, TaskResult $phase1, TaskResult $phase2): TaskResult
    {
        return $this->taskResult(
            startTime: $startTime,
            data: [
                'distribution'     => $phase2->get('distribution'),
                'min_satisfaction' => $phase1->get('min_satisfaction'),
            ],
            customMetadata: [
                'timeout_ms' => ($phase1->metadata['time_ms'] + $phase2->metadata['time_ms']),
                'phase1'     => [ ...$phase1->data, ...$phase1->metadata ],
                'phase2'     => [ ...$phase2->data, ...$phase2->metadata ],
                'artifacts'  => [...$phase1->metadata['artifacts'], ...$phase2->metadata['artifacts']],
            ],
        );
    }
}
