<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;

class WorstUnitsPruning extends TaskRunner
{
    protected Task $task = Task::WORST_UNITS_PRUNING;

    /**
     * Execute worst units pruning task.
     *
     * Identifies which units should be discarded to balance the lottery.
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        $startTime = microtime(true);

        $modFile = $this->files->write(
            'unit_selection_',
            '.mod',
            $this->modelGenerator->generateUnitSelectionModel()
        );

        $datFile = $this->files->write(
            'data_units_',
            '.dat',
            $this->dataGenerator->generateDataWithUnitCount($spec, count($spec->families))
        );

        $worstUnits = $this->runGlpk(
            $timeout,
            $modFile,
            $datFile,
            $this->solutionParser->extractUnusedUnits(...)
        );

        return $this->taskResult(
            startTime: $startTime,
            data: ['worst_units' => $worstUnits],
        );
    }
}
