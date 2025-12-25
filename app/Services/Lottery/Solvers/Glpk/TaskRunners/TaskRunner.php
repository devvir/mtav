<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk\TaskRunners;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataGenerator;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\ModelGenerator;
use App\Services\Lottery\Solvers\Glpk\SolutionParser;
use App\Services\Lottery\Solvers\Glpk\lib\Files;
use App\Services\Lottery\Solvers\Glpk\lib\Process;
use Closure;

abstract class TaskRunner
{
    abstract protected Task $task { get; }

    protected string $glpsolPath;
    protected Files $files;

    public function __construct(
        protected ModelGenerator $modelGenerator,
        protected DataGenerator $dataGenerator,
        protected SolutionParser $solutionParser,
    ) {
        $config = config('lottery.solvers.glpk.config');
        $this->glpsolPath = $config['glpsol_path'] ?? '/usr/bin/glpsol';
        $this->files = new Files($config['temp_dir'] ?? sys_get_temp_dir());
    }

    /**
     * Execute the GLPK task.
     *
     * @param  array  $context  Additional context data required by specific task runners
     */
    abstract public function execute(LotterySpec $spec, float $timeout, array $context = []): TaskResult;

    /**
     * Run GLPK solver on model and data files.
     *
     * Handles execution and cleanup automatically. Files are cleaned up in finally block.
     *
     * @param  int  $timeout  Timeout in seconds for GLPK execution
     * @param  Closure|null  $callback  Optional callback to process solution file (receives filepath as string)
     * @return mixed Solution file contents if no callback, otherwise callback result
     */
    protected function runGlpk(int $timeout, string $modFile, string $datFile, ?Closure $callback = null): mixed
    {
        $process = new Process($timeout);
        $solFile = $this->files->reserveSolutionPath();

        $command = sprintf(
            '%s --scale --model %s --data %s --tmlim %d --output %s 2>&1',
            escapeshellarg($this->glpsolPath),
            escapeshellarg($modFile),
            escapeshellarg($datFile),
            $process->getGlpkTimeout(),
            escapeshellarg($solFile)
        );

        try {
            $process->execute($command);
            $this->files->ensureReadable($solFile);

            return $callback ? $callback($solFile) : file_get_contents($solFile);
        } finally {
            $this->files->cleanup([$modFile, $datFile, $solFile]);
        }
    }

    /**
     * Helper to build TaskResult with automatic timing and artifacts collection.
     */
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
