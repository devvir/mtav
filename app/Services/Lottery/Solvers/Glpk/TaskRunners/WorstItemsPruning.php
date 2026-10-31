<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;

class WorstItemsPruning extends TaskRunner
{
    protected Task $task = Task::WORST_ITEMS_PRUNING;

    /**
     * Execute worst items pruning task.
     *
     * Identifies which items should be discarded to balance the lottery.
     */
    public function execute(LotterySpec $spec, float $timeout): TaskResult
    {
        $startTime = microtime(true);

        $modFile = $this->files->write(
            'item_selection_',
            '.mod',
            $this->modelGenerator->generateItemSelectionModel()
        );

        $datFile = $this->files->write(
            'data_items_',
            '.dat',
            $this->dataGenerator->generateDataWithItemCount($spec, count($spec->participants))
        );

        $worstItems = $this->runGlpk(
            $timeout,
            $modFile,
            $datFile,
            $this->solutionParser->extractUnusedItems(...)
        );

        return $this->taskResult(
            startTime: $startTime,
            data: ['worst_items' => $worstItems],
        );
    }
}
