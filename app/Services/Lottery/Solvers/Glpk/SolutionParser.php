<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use Illuminate\Support\Facades\Log;

/**
 * Parses GLPK solution files (.sol).
 */
class SolutionParser
{
    /**
     * Extract objective value from Phase 1 solution file.
     *
     * @param  string  $solFile  Path to .sol file
     * @return int Minimum satisfaction level (S)
     */
    public function extractObjective(string $solFile): int
    {
        $content = file_get_contents($solFile);

        // Look for objective value line: "Objective:  resultado = X (MINimum)"
        if (preg_match('/Objective:\s+\w+\s+=\s+(\d+(?:\.\d+)?)/i', $content, $matches)) {
            return (int) round((float) $matches[1]);
        }

        throw new GlpkException('Could not extract objective value from GLPK solution file.');
    }

    /**
     * Extract assignments from Phase 2 solution file.
     *
     * Parses variable values to find x[c,v] = 1 (family c assigned to unit v).
     *
     * @param  string  $solFile  Path to .sol file
     * @return array<int, string|int> Array of family_id => unit_id assignments (unit_id can be string for mock units)
     */
    public function extractAssignments(string $solFile): array
    {
        $content = file_get_contents($solFile);
        $picks = [];

        // Find all x[cN,vM] or x[cN,vMOCK_X] variables with Activity value of 1
        // Example line: "     1 x[c1,v10]    *              1             0             1"
        // Example line: "     1 x[c1,vMOCK_1]    *              1             0             1"
        // Format: column number, variable name, *, activity (multiple spaces between)
        preg_match_all('/x\[c(\d+),v([A-Za-z0-9_]+)\]\s+\*?\s+(\d+(?:\.\d+)?)/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $familyId = (int) $match[1];
            $unitId = is_numeric($match[2]) ? (int) $match[2] : $match[2]; // Keep strings as strings, convert numbers to int
            $activity = (float) $match[3];

            // Only include assignments where activity is close to 1
            if ($activity >= 0.99) {
                $picks[$familyId] = $unitId;
            }
        }

        if (empty($picks)) {
            // Check if GLPK explicitly reported the problem as infeasible
            if (str_contains($content, 'SOLUTION IS INFEASIBLE') || str_contains($content, 'INTEGER EMPTY')) {
                throw new GlpkInfeasibleException('GLPK determined the problem has no feasible solution.');
            }

            // Otherwise, this is unexpected - log for debugging
            Log::warning('No assignments found in GLPK solution file', [
                'file'          => $solFile,
                'file_size'     => filesize($solFile),
                'content'       => $content,
                'matches_found' => count($matches),
            ]);
            throw new GlpkException('No assignments found in GLPK solution file.');
        }

        return $picks;
    }

    /**
     * Extract unused units from unit selection solution file.
     *
     * Parses variable values to find u[v] = 0 (unit v not selected).
     *
     * @param  string  $solFile  Path to .sol file
     * @return array<int> Array of unit IDs that were not selected
     */
    public function extractUnusedUnits(string $solFile): array
    {
        $content = file_get_contents($solFile);
        $unusedUnits = [];

        // Find all u[vM] variables with Activity value of 0
        // Example line: "     1 u[v10]    *              0             0             1"
        preg_match_all('/u\[v(\d+)\]\s+\*?\s+(\d+(?:\.\d+)?)/i', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $unitId = (int) $match[1];
            $activity = (float) $match[2];

            // Only include units where activity is close to 0
            if ($activity < 0.01) {
                $unusedUnits[] = $unitId;
            }
        }

        return $unusedUnits;
    }
}
