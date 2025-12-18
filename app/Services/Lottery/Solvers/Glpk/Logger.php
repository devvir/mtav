<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Handles comprehensive logging for GLPK execution failures.
 *
 * Captures all relevant debugging information including phase results,
 * spec data, and GLPK artifact contents for troubleshooting.
 */
class Logger
{
    /**
     * Log comprehensive debugging information when Phase 1 fails.
     */
    public function logPhase1Failure(
        Exception $e,
        LotterySpec $spec,
        string $modFile,
        string $datFile,
        ?string $solFile
    ): void {
        $context = [
            'exception_type'    => get_class($e),
            'exception_message' => $e->getMessage(),
            'phase'             => 1,
            'family_count'      => count($spec->families),
            'unit_count'        => count($spec->units),
            'spec'              => [
                'units'       => $spec->units,
                'preferences' => $spec->families,
            ],
            'artifacts' => [
                'model_file'    => $modFile,
                'data_file'     => $datFile,
                'solution_file' => $solFile,
            ],
        ];

        // Include file contents if they exist
        if (file_exists($modFile)) {
            $context['model_content'] = file_get_contents($modFile);
        }
        if (file_exists($datFile)) {
            $context['data_content'] = file_get_contents($datFile);
        }
        if ($solFile && file_exists($solFile)) {
            $context['solution_content'] = file_get_contents($solFile);
        }

        if ($e instanceof GlpkInfeasibleException) {
            Log::warning('GLPK Phase 1 determined problem is infeasible', $context);
        } else {
            Log::error('GLPK Phase 1 failed unexpectedly', $context);
        }
    }

    /**
     * Log comprehensive debugging information when Phase 2 fails.
     */
    public function logPhase2Failure(
        Exception $e,
        LotterySpec $spec,
        int $minSatisfaction,
        string $modFile,
        string $datFile,
        ?string $solFile
    ): void {
        $context = [
            'exception_type'    => get_class($e),
            'exception_message' => $e->getMessage(),
            'phase'             => 2,
            'phase1_result'     => $minSatisfaction,
            'family_count'      => count($spec->families),
            'unit_count'        => count($spec->units),
            'spec'              => [
                'units'       => $spec->units,
                'preferences' => $spec->families,
            ],
            'artifacts' => [
                'model_file'    => $modFile,
                'data_file'     => $datFile,
                'solution_file' => $solFile,
            ],
        ];

        // Include file contents if they exist
        if (file_exists($modFile)) {
            $context['model_content'] = file_get_contents($modFile);
        }
        if (file_exists($datFile)) {
            $context['data_content'] = file_get_contents($datFile);
        }
        if ($solFile && file_exists($solFile)) {
            $context['solution_content'] = file_get_contents($solFile);
        }

        if ($e instanceof GlpkInfeasibleException) {
            Log::warning('GLPK Phase 2 determined problem is infeasible', $context);
        } else {
            Log::error('GLPK Phase 2 failed unexpectedly', $context);
        }
    }
}
