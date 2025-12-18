<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\Solvers\Glpk\DataGenerator;
use App\Services\Lottery\Solvers\Glpk\Enums\Tasks;
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
    public function make(Tasks $task): TaskRunner
    {
        return match ($task) {
            Tasks::MIN_SATISFACTION    => app(MinSatisfaction::class),
            Tasks::UNIT_DISTRIBUTION   => app(UnitDistribution::class),
            Tasks::WORST_UNITS_PRUNING => app(WorstUnitsPruning::class),
        };
    }
}
