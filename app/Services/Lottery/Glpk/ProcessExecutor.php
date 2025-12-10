<?php

namespace App\Services\Lottery\Glpk;

use App\Services\Lottery\Exceptions\GlpkException;
use App\Services\Lottery\Exceptions\GlpkTimeoutException;

/**
 * Executes external processes with timeout control.
 *
 * Handles process execution with PHP-level timeout enforcement,
 * output capturing, and graceful termination of hung processes.
 */
class ProcessExecutor
{
    public function __construct(
        protected int $timeout,
        protected string $tempDir,
    ) {
        // ...
    }

    /**
     * Execute a shell command with timeout control.
     *
     * Uses proc_open for true timeout control instead of exec(),
     * allowing forced termination if the process exceeds the timeout.
     *
     * @param  string  $command  Shell command to execute
     * @return string Process output
     *
     * @throws GlpkException if process fails
     * @throws GlpkTimeoutException if process exceeds timeout
     */
    public function execute(string $command): string
    {
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if ($process === false) {
            throw new GlpkException("Failed to start process: {$command}");
        }

        $startTime = time();
        $output = '';

        try {
            $output = $this->pollProcessOutput($process, $pipes, $startTime);
        } finally {
            $this->cleanupProcess($process, $pipes);
        }

        return $output;
    }

    /**
     * Poll process output with timeout enforcement.
     *
     * @param resource $process Process resource
     * @param array<int, resource> $pipes Pipe resources
     * @param int $startTime Start timestamp
     * @return string Captured output
     *
     * @throws GlpkTimeoutException if timeout is exceeded
     */
    private function pollProcessOutput($process, array $pipes, int $startTime): string
    {
        $output = '';

        while (true) {
            if (!is_resource($process)) {
                break;
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            // Check for PHP-level timeout (failsafe in case GLPK's --tmlim fails)
            $elapsedSeconds = time() - $startTime;
            if ($elapsedSeconds > $this->timeout) {
                throw new GlpkTimeoutException(
                    "Process execution timed out after {$this->timeout} seconds (PHP-level timeout)."
                );
            }

            // Non-blocking read from pipes
            stream_set_blocking($pipes[1], false);
            $chunk = fread($pipes[1], 4096);
            if ($chunk !== '') {
                $output .= $chunk;
            }

            usleep(100000); // Sleep 100ms before checking again
        }

        // Read remaining output
        stream_set_blocking($pipes[1], true);
        while (($chunk = fread($pipes[1], 4096)) !== '') {
            $output .= $chunk;
        }

        return $output;
    }

    /**
     * Clean up process resources.
     *
     * @param resource $process Process resource
     * @param array<int, resource> $pipes Pipe resources
     */
    private function cleanupProcess($process, array $pipes): void
    {
        // If still running (e.g., timeout), kill it
        if (is_resource($process)) {
            $status = proc_get_status($process);
            if ($status['running']) {
                proc_terminate($process, 9); // SIGKILL
            }
        }

        @fclose($pipes[1] ?? null);
        @fclose($pipes[2] ?? null);
        @proc_close($process);
    }
}
