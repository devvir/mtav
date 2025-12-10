<?php

// Copilot - Pending review

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Detects degenerate lottery cases where GLPK would timeout.
 *
 * Degenerate cases include:
 * - Large problems (> 10×10) where GLPK exhibits exponential behavior
 * - High preference similarity (≥80% of families have identical preferences)
 * - High preference opposition (≥80% split into opposite preference patterns)
 *
 * This detector is internal to the GLPK solver and used to determine
 * whether to fall back to the greedy approximation algorithm.
 */
class DegeneracyDetector
{
    /**
     * Detect if a lottery spec represents a degenerate case.
     *
     * @param LotterySpec $spec The lottery specification to analyze
     * @return bool True if degeneracy is detected
     */
    public function isDegenerate(LotterySpec $spec): bool
    {
        // Check 1: Problem size threshold (>10×10 is known to cause GLPK timeouts)
        if ($this->isSizeThresholdExceeded($spec)) {
            return true;
        }

        // Check 2: High preference similarity (≥80% identical)
        if ($this->hasDegeneratePreferenceSimilarity($spec)) {
            return true;
        }

        // Check 3: High preference opposition (≥80% split between opposite patterns)
        if ($this->hasDegeneratePreferenceOpposition($spec)) {
            return true;
        }

        return false;
    }

    /**
     * Check if problem exceeds size threshold (>10×10).
     *
     * Empirically, GLPK exhibits exponential behavior for problems larger than 10×10,
     * particularly with degenerate preference patterns.
     */
    private function isSizeThresholdExceeded(LotterySpec $spec): bool
    {
        $sizeThreshold = config('lottery.solvers.glpk.config.degeneracy_detection.size_threshold', 11);

        return max($spec->familyCount(), $spec->unitCount()) >= $sizeThreshold;
    }

    /**
     * Check if ≥80% of families have identical preference patterns.
     *
     * When all families prefer the same units in the same order, GLPK faces
     * infinite equivalent solutions and cycles indefinitely searching for the best one.
     */
    private function hasDegeneratePreferenceSimilarity(LotterySpec $spec): bool
    {
        $threshold = config('lottery.solvers.glpk.config.degeneracy_detection.similarity_threshold', 0.80);

        // Build a preference pattern signature for each family
        $patterns = [];
        foreach ($spec->families as $familyId => $preferences) {
            // Create a signature of preference order (first 5 units)
            $pattern = implode('-', array_slice($preferences, 0, 5));
            $patterns[$pattern] = ($patterns[$pattern] ?? 0) + 1;
        }

        // Find the most common pattern
        $maxPatternCount = max($patterns);
        $similarityRatio = $maxPatternCount / count($spec->families);

        return $similarityRatio >= $threshold;
    }

    /**
     * Check if ≥80% of families are split into opposite preference patterns.
     *
     * When families are divided into groups with reversed preferences,
     * the preference space is maximally degenerate with infinite equivalent solutions.
     */
    private function hasDegeneratePreferenceOpposition(LotterySpec $spec): bool
    {
        if (count($spec->families) < 4) {
            return false; // Need at least 4 families to detect opposition
        }

        $threshold = config('lottery.solvers.glpk.config.degeneracy_detection.opposition_threshold', 0.80);

        // Build preference pattern signatures (first 3 units for pattern matching)
        $patterns = [];
        foreach ($spec->families as $familyId => $preferences) {
            // Get first 3 units to create a signature
            $pattern = implode('-', array_slice($preferences, 0, 3));
            if (!isset($patterns[$pattern])) {
                $patterns[$pattern] = [];
            }
            $patterns[$pattern][] = $familyId;
        }

        // If there's only one dominant pattern, not opposition
        if (count($patterns) <= 1) {
            return false;
        }

        // Check if the two most common patterns are reversed versions of each other
        $patternCounts = array_map('count', $patterns);
        arsort($patternCounts);
        $topPatterns = array_keys($patternCounts);

        if (count($topPatterns) < 2) {
            return false;
        }

        // Get sample families from each top pattern
        $pattern1Families = $patterns[$topPatterns[0]];
        $pattern2Families = $patterns[$topPatterns[1]];

        if (empty($pattern1Families) || empty($pattern2Families)) {
            return false;
        }

        // Check if preferences are mostly reversed
        $familyId1 = $pattern1Families[0];
        $familyId2 = $pattern2Families[0];

        $pref1 = $spec->families[$familyId1];
        $pref2 = $spec->families[$familyId2];

        // Compare reversed versions
        $reversedPref2 = array_reverse($pref2);
        $matchCount = 0;
        $totalChecks = min(3, count($pref1)); // Check first 3 units

        for ($i = 0; $i < $totalChecks; $i++) {
            if ($pref1[$i] === $reversedPref2[$i]) {
                $matchCount++;
            }
        }

        // If majority of checked units match when reversed, it's opposition
        $oppositionRatio = $matchCount / $totalChecks;

        return $oppositionRatio >= 0.67; // 2 out of 3 is ~67%, indicates likely opposition
    }
}
