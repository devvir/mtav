<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk\lib;

use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;

/**
 * Executes GLPK processes with timeout control.
 *
 * Handles process execution with PHP-level timeout enforcement,
 * output capturing, and graceful termination of hung processes.
 */
class Process
{
    public function __construct(
        protected int $timeout,
    ) {
        // ...
    }

    /**
     * Get the GLPK timeout value.
     */
    public function getGlpkTimeout(): int
    {
        return $this->timeout;
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
            throw (new GlpkException(
                "Failed to start process: {$command}"
            ))->with([
                'command' => $command,
                'timeout' => $this->timeout,
            ]);
        }

        $startTime = time();
        $exitCode = -1;

        try {
            [$output, $stderr] = $this->pollProcessOutput($process, $pipes, $startTime);

            // Close pipes before calling proc_close
            if (is_resource($pipes[1] ?? null)) {
                fclose($pipes[1]);
            }
            if (is_resource($pipes[2] ?? null)) {
                fclose($pipes[2]);
            }

            // Get final exit code - must be called after process completes
            $exitCode = proc_close($process);
        } catch (GlpkTimeoutException $e) {
            // Cleanup on timeout and re-throw
            $this->cleanup($process, $pipes);
            throw $e;
        } catch (GlpkException $e) {
            // Cleanup on error and re-throw
            $this->cleanup($process, $pipes);
            throw $e;
        }

        // Check for process failure
        if ($exitCode !== 0) {
            $errorMsg = trim($stderr ?: $output) ?: "Process exited with code {$exitCode}";
            throw (new GlpkException(
                "GLPK execution failed: {$errorMsg}"
            ))->with([
                'exit_code' => $exitCode,
                'command'   => $command,
                'stdout'    => $output,
                'stderr'    => $stderr,
                'timeout'   => $this->timeout,
            ]);
        }

        return $output;
    }

    /**
     * Poll process output with timeout enforcement.
     *
     * @param resource $process Process resource
     * @param array<int, resource> $pipes Pipe resources
     * @param int $startTime Start timestamp
     * @return array{string, string} Array of [stdout, stderr]
     *
     * @throws GlpkTimeoutException if timeout is exceeded
     */
    private function pollProcessOutput($process, array $pipes, int $startTime): array
    {
        $output = '';
        $stderr = '';

        $failsafeTimeout = (int) ceil($this->timeout * 1.2);

        while (true) {
            if (!is_resource($process)) {
                break;
            }

            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }

            // Check for PHP-level timeout (failsafe only - should trigger after GLPK's --tmlim)
            $elapsedSeconds = time() - $startTime;
            if ($elapsedSeconds > $failsafeTimeout) {
                throw (new GlpkTimeoutException(
                    "Process execution exceeded PHP failsafe timeout after {$failsafeTimeout} seconds. GLPK's internal timeout may have failed."
                ))->with([
                    'elapsed_seconds'    => $elapsedSeconds,
                    'failsafe_timeout'   => $failsafeTimeout,
                    'configured_timeout' => $this->timeout,
                    'output'             => $output,
                    'stderr'             => $stderr,
                ]);
            }

            // Non-blocking read from pipes
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            $chunk = fread($pipes[1], 4096);
            if ($chunk !== '' && $chunk !== false) {
                $output .= $chunk;
            }

            $errChunk = fread($pipes[2], 4096);
            if ($errChunk !== '' && $errChunk !== false) {
                $stderr .= $errChunk;
            }

            usleep(100000); // Sleep 100ms before checking again
        }

        // Read remaining output
        stream_set_blocking($pipes[1], true);
        stream_set_blocking($pipes[2], true);

        while (($chunk = fread($pipes[1], 4096)) !== '' && $chunk !== false) {
            $output .= $chunk;
        }

        while (($errChunk = fread($pipes[2], 4096)) !== '' && $errChunk !== false) {
            $stderr .= $errChunk;
        }

        // Check if GLPK's internal timeout was triggered
        // GLPK exits successfully (code 0) but writes "TIME LIMIT EXCEEDED" to output
        if (str_contains($output, 'TIME LIMIT EXCEEDED')) {
            throw (new GlpkTimeoutException(
                "GLPK internal timeout triggered after {$this->timeout} seconds (--tmlim)."
            ))->with([
                'timeout' => $this->timeout,
                'output'  => $output,
                'stderr'  => $stderr,
            ]);
        }

        return [$output, $stderr];
    }

    /**
     * Clean up process resources.
     *
     * Terminates the process if still running and closes all pipe handles.
     *
     * @param resource $process Process resource
     * @param array<int, resource> $pipes Pipe resources
     */
    private function cleanup($process, array $pipes): void
    {
        // If still running (e.g., timeout), kill it
        if (is_resource($process)) {
            $status = proc_get_status($process);

            if ($status['running']) {
                proc_terminate($process, 9); // SIGKILL
            }
        }

        is_resource($pipes[1]) && @fclose($pipes[1]);
        is_resource($pipes[2]) && @fclose($pipes[2]);
        @proc_close($process);
    }
}
