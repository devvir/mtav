<?php

namespace App\Services\Lottery\Solvers\Glpk\lib;

use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\RuntimeException as ProcessException;

/**
 * Runs GLPK's solver (glpsol) on a model and its data, writing the solution to a file, within
 * a time limit: GLPK's own (`--tmlim`, in whole seconds), and ours a little past it, in case
 * GLPK's own fails to stop it.
 */
class Glpsol
{
    /** How much longer than GLPK's own limit it may run before it is stopped */
    private const FAILSAFE = 1.2;

    public function __construct(private string $path)
    {
    }

    /**
     * @param int $timeout  seconds GLPK may take
     *
     * @throws GlpkTimeoutException when either time limit is reached
     * @throws GlpkException when it cannot run, or fails
     */
    public function solve(string $modFile, string $datFile, string $solFile, int $timeout): void
    {
        $command = sprintf(
            '%s --scale --model %s --data %s --tmlim %d --output %s 2>&1',
            escapeshellarg($this->path),
            escapeshellarg($modFile),
            escapeshellarg($datFile),
            $timeout,
            escapeshellarg($solFile),
        );

        // A limit of 0 is none at all to Process: at least a second, then
        $failsafe = max(1, (int) ceil($timeout * self::FAILSAFE));

        try {
            $result = Process::timeout($failsafe)->run($command);
        } catch (ProcessTimedOutException $e) {
            throw (new GlpkTimeoutException(
                "Process execution exceeded PHP failsafe timeout after {$failsafe} seconds. GLPK's internal timeout may have failed.",
                previous: $e,
            ))->with([
                'failsafe_timeout'   => $failsafe,
                'configured_timeout' => $timeout,
                'output'             => $e->result->output(),
                'stderr'             => $e->result->errorOutput(),
            ]);
        } catch (ProcessException $e) {
            throw (new GlpkException("Failed to start process: {$command}", previous: $e))->with([
                'command' => $command,
                'timeout' => $timeout,
            ]);
        }

        $output = $result->output();
        $stderr = $result->errorOutput();

        // GLPK stopped by its own limit exits successfully, saying so in its output
        if (str_contains($output, 'TIME LIMIT EXCEEDED')) {
            throw (new GlpkTimeoutException("GLPK internal timeout triggered after {$timeout} seconds (--tmlim)."))->with([
                'timeout' => $timeout,
                'output'  => $output,
                'stderr'  => $stderr,
            ]);
        }

        if (! $result->successful()) {
            $error = trim($stderr ?: $output) ?: "Process exited with code {$result->exitCode()}";

            throw (new GlpkException("GLPK execution failed: {$error}"))->with([
                'exit_code' => $result->exitCode(),
                'command'   => $command,
                'stdout'    => $output,
                'stderr'    => $stderr,
                'timeout'   => $timeout,
            ]);
        }
    }
}
