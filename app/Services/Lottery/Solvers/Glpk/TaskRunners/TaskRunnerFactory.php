<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\Solvers\Glpk\Enums\Task;

/** A fresh runner for each task, from the container */
class TaskRunnerFactory
{
    public function make(Task $task): TaskRunner
    {
        return match ($task) {
            /**
             * Atomic runners
             */
            Task::MIN_SATISFACTION    => app(MinSatisfaction::class),
            Task::ITEM_DISTRIBUTION   => app(ItemDistribution::class),
            Task::WORST_ITEMS_PRUNING => app(WorstItemsPruning::class),

            /**
             * Composite runners
             */
            Task::GLPK_DISTRIBUTION   => app(GlpkDistribution::class),
            Task::HYBRID_DISTRIBUTION => app(HybridDistribution::class),
        };
    }
}
