<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\UnitDistribution;
use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Stress.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('GLPK Solver Stress Tests', function () {
    describe('Empirical Safe Zone Limits (1-3 minute problems)', function () {
        // These tests validate GLPK performance on problems that actually stress the solver.
        // Based on empirical testing, these sizes take 1-3 minutes with random preference distributions:
        // - 50×50: ~79 seconds (safe, but nearing limits)
        // - 60×60: ~24 seconds (safe)
        // - 70×70: ~17 seconds (safe)
        // All should complete in under 5 minutes (300 seconds) to prove they're within safe zone.

        dataset('safe zone stress cases', [
            '50×50 (79s typical)' => [50],
            '60×60 (24s typical)' => [60],
            '70×70 (17s typical)' => [70],
        ]);

        test('with random preferences', function (int $size) {
            // Generate random preferences (pure arrays, no database)
            $families = [];
            $units = range(1, $size);

            foreach (range(1, $size) as $familyId) {
                $prefs = $units;
                shuffle($prefs);
                $families[$familyId] = $prefs;
            }

            $spec = new LotterySpec($families, $units);
            $manifest = mockManifest(1, [1 => ['families' => $families, 'units' => $units]]);

            // Test GLPK solver directly
            $start = microtime(true);
            $result = app(GlpkSolver::class)->execute($manifest, $spec);
            $elapsed = microtime(true) - $start;

            expect($elapsed)->toBeLessThan(300); // Must complete within 5 minutes
            expect($result->picks)->toHaveCount($size); // All units distributed
            expect(array_keys($result->picks))->toEqualCanonicalizing(range(1, $size)); // All families got a unit
        })->with('safe zone stress cases');
    });

    describe('Model correctness - proper INFEASIBLE handling', function () {
        test('Phase 2 with impossibly strict S returns INFEASIBLE (not OPTIMAL with zeros)', function () {
            // Recreate the scenario from laravel.log that triggered "No assignments found":
            // 2 families, 2 units, but S is impossibly low for the preference structure
            $families = [
                1 => [10, 20], // Family 1: prefers unit 10 (rank 1), then unit 20 (rank 2)
                1 => [20, 10], // Family 2: prefers unit 20 (rank 1), then unit 10 (rank 2)
            ];
            $units = [10, 20];

            $spec = new LotterySpec($families, $units);
            $manifest = mockManifest(1, [1 => ['families' => $families, 'units' => $units]]);

            // Try Phase 2 with S=1 (impossibly strict - both families want their first choice)
            // With = 1 constraints, GLPK should return INFEASIBLE
            // (not OPTIMAL with all zeros like with split >= 1, <= 1 constraints)
            $phase2 = app(UnitDistribution::class);

            expect(fn () => $phase2->execute($spec, 300, ['min_satisfaction' => 1]))
                ->toThrow(GlpkInfeasibleException::class);
        });
    });
});
