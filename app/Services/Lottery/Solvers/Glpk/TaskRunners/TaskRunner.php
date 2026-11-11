<?php

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataGenerator;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\lib\Files;
use App\Services\Lottery\Solvers\Glpk\lib\Glpsol;
use App\Services\Lottery\Solvers\Glpk\ModelGenerator;
use App\Services\Lottery\Solvers\Glpk\SolutionParser;
use Closure;

/**
 * One GLPK task: writes its model and data, runs glpsol on them and reads the solution. Every
 * file it writes is kept as an artifact, for the run's audit.
 */
abstract class TaskRunner
{
    abstract protected Task $task { get; }

    protected Files $files;

    protected Glpsol $glpsol;

    /** @var array<string, mixed> what a task needs from whoever runs it (e.g. `min_satisfaction`) */
    protected array $context = [];

    public function __construct(
        protected ModelGenerator $modelGenerator,
        protected DataGenerator $dataGenerator,
        protected SolutionParser $solutionParser,
    ) {
        $config = config('lottery.solvers.glpk.config');

        $this->glpsol = new Glpsol($config['glpsol_path'] ?? '/usr/bin/glpsol');
        $this->files = new Files($config['temp_dir'] ?? sys_get_temp_dir());
    }

    /** @param array<string, mixed> $context */
    public function withContext(array $context): self
    {
        $this->context = $context;

        return $this;
    }

    /** @param float $timeout  seconds the task may take */
    abstract public function execute(LotterySpec $spec, float $timeout): TaskResult;

    /**
     * Run glpsol on a model and its data, and read what it solved; the files go afterwards,
     * whatever happened.
     *
     * @param int $timeout  seconds glpsol may take
     * @param Closure(string): mixed $read  reads the solution file
     */
    protected function runGlpk(int $timeout, string $modFile, string $datFile, Closure $read): mixed
    {
        $solFile = $this->files->reserveSolutionPath();

        try {
            $this->glpsol->solve($modFile, $datFile, $solFile, $timeout);
            $this->files->ensureReadable($solFile);

            return $read($solFile);
        } finally {
            $this->files->cleanup([$modFile, $datFile, $solFile]);
        }
    }

    /** What the task found, with how long it took and every file it wrote */
    protected function taskResult(float $startTime, array $data, array $customMetadata = []): TaskResult
    {
        $timeMs = (microtime(true) - $startTime) * 1000;

        return new TaskResult(
            task: $this->task,
            data: $data,
            metadata: [
                'time_ms'   => round($timeMs, 2),
                'artifacts' => $this->files->getArtifacts(),
                ...$customMetadata,
            ],
        );
    }
}
