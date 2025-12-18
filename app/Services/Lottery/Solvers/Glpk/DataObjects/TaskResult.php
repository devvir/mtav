<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk\DataObjects;

use App\Services\Lottery\Solvers\Glpk\Enums\Tasks;

class TaskResult
{
    public function __construct(
        public readonly Tasks $task,
        public readonly array $data,
        public readonly array $metadata,
    ) {
        // ...
    }

    /**
     * Get a specific value from the result data.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all result data.
     */
    public function all(): array
    {
        return $this->data;
    }
}
