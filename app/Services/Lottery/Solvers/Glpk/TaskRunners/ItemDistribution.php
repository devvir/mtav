<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use InvalidArgumentException;

class ItemDistribution extends TaskRunner
{
    protected Task $task = Task::ITEM_DISTRIBUTION;

    /**
     * Phase 2: everyone as satisfied as possible together, nobody placed worse than S, which
     * it takes from its context (`min_satisfaction`). No assignment meets S: GlpkInfeasibleException.
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        $minSatisfaction = $this->context['min_satisfaction'] ?? null;

        if ($minSatisfaction === null) {
            throw new InvalidArgumentException('ItemDistribution requires min_satisfaction in context');
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
