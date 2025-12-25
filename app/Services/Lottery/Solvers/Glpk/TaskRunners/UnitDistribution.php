<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use InvalidArgumentException;

class UnitDistribution extends TaskRunner
{
    protected Task $task = Task::UNIT_DISTRIBUTION;

    /**
     * Execute unit distribution task (Phase 2).
     *
     * Maximizes overall satisfaction given minimum satisfaction constraint from Phase 1.
     *
     * @param  array  $context  Must contain 'min_satisfaction' key
     */
    public function execute(LotterySpec $spec, float $timeout, array $context = []): TaskResult
    {
        $minSatisfaction = $context['min_satisfaction'] ?? null;

        if ($minSatisfaction === null) {
            throw new InvalidArgumentException('UnitDistribution requires min_satisfaction in context');
        }

        $startTime = microtime(true);

        $modFile = $this->files->write('phase2_', '.mod', $this->modelGenerator->generatePhase2Model());
        $datFile = $this->files->write('data_s_', '.dat', $this->dataGenerator->generateDataWithS($spec, $minSatisfaction));

        $distribution = $this->runGlpk(
            $timeout,
            $modFile,
            $datFile,
            $this->solutionParser->extractAssignments(...)
        );

        return $this->taskResult(
            startTime: $startTime,
            data: ['distribution' => $distribution],
            customMetadata: ['min_satisfaction' => $minSatisfaction]
        );
    }
}
