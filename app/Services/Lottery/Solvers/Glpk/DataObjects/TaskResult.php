<?php

namespace App\Services\Lottery\Solvers\Glpk\DataObjects;

use App\Services\Lottery\Solvers\Glpk\Enums\Task;

/**
 * What a GLPK task found (`data`), and how it went (`metadata`: its time, the files it wrote,
 * and what each task adds), for the run's audit.
 */
class TaskResult
{
    public function __construct(
        public readonly Task $task,
        public readonly array $data,
        public readonly array $metadata,
    ) {
    }

    /** One value of what it found */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
