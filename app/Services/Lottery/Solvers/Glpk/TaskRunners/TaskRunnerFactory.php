<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\Solvers\Glpk\DataGenerator;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\ModelGenerator;
use App\Services\Lottery\Solvers\Glpk\SolutionParser;

class TaskRunnerFactory
{
    public function __construct(
        protected ModelGenerator $modelGenerator,
        protected DataGenerator $dataGenerator,
        protected SolutionParser $solutionParser,
    ) {
        // ...
    }

    /**
     * Create a task runner for the specified task.
     */
    public function make(Task $task): TaskRunner
    {
        return match ($task) {
            /**
             * Atomic runners
             */
            Task::MIN_SATISFACTION    => app(MinSatisfaction::class),
            Task::UNIT_DISTRIBUTION   => app(UnitDistribution::class),
            Task::WORST_UNITS_PRUNING => app(WorstUnitsPruning::class),

            /**
             * Composite runners
             */
            Task::GLPK_DISTRIBUTION   => app(GlpkDistribution::class),
            Task::HYBRID_DISTRIBUTION => app(HybridDistribution::class),
        };
    }
}
