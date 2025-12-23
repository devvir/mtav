<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');

    config()->set('lottery.default', 'glpk');
    config()->set('lottery.solvers.glpk.config.glpk_phase1_timeout', 3);
    config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 100);
});

describe('Hybrid Phase 1 Strategy', function () {
    test('uses Phase 1 optimization when it completes within timeout', function () {
        // Small problem that Phase 1 should solve quickly
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        // Set generous timeout
        config()->set('lottery.solvers.glpk.config.timeout', 60);

        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should successfully find solution
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['families'])->toBeEmpty();
        expect($result->orphans['units'])->toBeEmpty();

        // Check that Phase 1 strategy was used (via CUSTOM audit metadata)
        $customAudits = \App\Models\Event::find($manifest->lotteryId)
            ->audits()
            ->where('type', \App\Services\Lottery\Enums\LotteryAuditType::CUSTOM->value)
            ->get();

        // Find MIN_SATISFACTION task audit
        $minSatAudit = $customAudits->first(function ($audit) {
            return $audit->audit['task'] === 'min_satisfaction';
        });

        expect($minSatAudit)->not->toBeNull();
        expect($minSatAudit->audit['metadata']['strategy'])->toBe('glpk');
        // When glpk succeeds, no binsearch fields should exist
        expect($minSatAudit->audit['metadata'])->not->toHaveKey('binsearch_iterations');
        expect($minSatAudit->audit['metadata'])->not->toHaveKey('binsearch_time_ms');
    });

    test('falls back to binary search when Phase 1 times out', function () {
        // Create a larger problem that might timeout with very short limit
        $families = [];
        $units = range(100, 124); // 25 units

        foreach (range(1, 25) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        // Set extremely short timeout to force Phase 1 timeout
        config()->set('lottery.solvers.glpk.config.timeout', 1); // 1 second total

        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Binary search should still find a solution
        expect($result->picks)->toHaveCount(25);
        expect($result->orphans['families'])->toBeEmpty();
        expect($result->orphans['units'])->toBeEmpty();

        // Verify all families assigned
        expect(array_keys($result->picks))->each->toBeIn(range(1, 25));
    });

    test('binary search finds correct minimum satisfaction value', function () {
        // Problem where we know the optimal S
        // All families want same unit first, then diverge
        $families = [
            1 => [10, 20, 30],  // Wants 10 first
            2 => [10, 30, 20],  // Wants 10 first
            3 => [10, 20, 30],  // Wants 10 first
        ];
        $units = [10, 20, 30];

        // Optimal: one family gets 10 (rank 1), others get 20/30 (rank 2)
        // So minimum S should be 2 (worst family gets their 2nd choice)

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        config()->set('lottery.solvers.glpk.config.timeout', 60);

        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Verify correct assignment count
        expect($result->picks)->toHaveCount(3);

        // Calculate worst rank
        $worstRank = 0;
        foreach ($result->picks as $familyId => $unitId) {
            $rank = array_search($unitId, $families[$familyId]) + 1;
            $worstRank = max($worstRank, $rank);
        }

        // Worst family should get rank 2 (max-min fairness)
        expect($worstRank)->toBe(2);

        // Verify via audit metadata
        $customAudits = \App\Models\Event::find($manifest->lotteryId)
            ->audits()
            ->where('type', \App\Services\Lottery\Enums\LotteryAuditType::CUSTOM->value)
            ->get();

        $minSatAudit = $customAudits->first(function ($audit) {
            return $audit->audit['task'] === 'min_satisfaction';
        });

        expect($minSatAudit->audit['result']['min_satisfaction'])->toBe(2);
    });

    test('hybrid strategy includes proper metadata for both approaches', function () {
        // Small problem for Phase 1 success
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        config()->set('lottery.solvers.glpk.config.timeout', 60);

        app(GlpkSolver::class)->execute($manifest, $spec);

        $customAudits = \App\Models\Event::find($manifest->lotteryId)
            ->audits()
            ->where('type', \App\Services\Lottery\Enums\LotteryAuditType::CUSTOM->value)
            ->get();

        $minSatAudit = $customAudits->first(function ($audit) {
            return $audit->audit['task'] === 'min_satisfaction';
        });

        $metadata = $minSatAudit->audit['metadata'];

        // Should have strategy indicator
        expect($metadata)->toHaveKey('strategy');
        expect($metadata['strategy'])->toBeIn(['glpk', 'binsearch']);

        // Should have timing info from buildMetadata
        expect($metadata)->toHaveKey('time_ms');
        expect($metadata['time_ms'])->toBeNumeric();
        expect($metadata['time_ms'])->toBeGreaterThan(0);

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
        expect($metadata['artifacts'])->not->toBeEmpty();

        // Strategy-specific fields
        if ($metadata['strategy'] === 'glpk') {
            // GLPK strategy fields
            expect($metadata)->toHaveKey('glpk_time_ms');
            expect($metadata['glpk_time_ms'])->toBeNumeric();
            expect($metadata)->toHaveKey('glpk_timeout_ms');
            expect($metadata['glpk_timeout_ms'])->toBeNumeric();
            // Should NOT have binsearch fields
            expect($metadata)->not->toHaveKey('binsearch_iterations');
            expect($metadata)->not->toHaveKey('binsearch_time_ms');
        } else {
            // Binary search strategy fields
            expect($metadata)->toHaveKey('binsearch_iterations');
            expect($metadata['binsearch_iterations'])->toBeInt();
            expect($metadata['binsearch_iterations'])->toBeGreaterThan(0);
            expect($metadata)->toHaveKey('binsearch_time_ms');
            expect($metadata['binsearch_time_ms'])->toBeNumeric();
            expect($metadata)->toHaveKey('binsearch_timeout_ms');
            // Should also have glpk timing (from failed attempt)
            expect($metadata)->toHaveKey('glpk_time_ms');
            expect($metadata['glpk_time_ms'])->toBeNumeric();
            expect($metadata)->toHaveKey('glpk_timeout_ms');
        }
    });

    test('Phase 1 timeout preserves artifacts from failed attempt', function () {
        // Medium problem that might timeout
        $families = [];
        $units = range(100, 119); // 20 units

        foreach (range(1, 20) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        // Very short timeout to likely trigger fallback
        config()->set('lottery.solvers.glpk.config.timeout', 1);

        app(GlpkSolver::class)->execute($manifest, $spec);

        $customAudits = \App\Models\Event::find($manifest->lotteryId)
            ->audits()
            ->where('type', \App\Services\Lottery\Enums\LotteryAuditType::CUSTOM->value)
            ->get();

        // Check all task audits have artifacts
        expect($customAudits->count())->toBeGreaterThan(0);

        $customAudits->each(function ($audit) {
            expect($audit->audit['metadata']['artifacts'])->toBeArray();
            expect($audit->audit['metadata']['artifacts'])->not->toBeEmpty();

            // Each audit should have .mod, .dat, .sol files
            $extensions = array_map(
                fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
                array_keys($audit->audit['metadata']['artifacts'])
            );

            expect($extensions)->toContain('mod');
            expect($extensions)->toContain('dat');
            expect($extensions)->toContain('sol');
        });
    });
});
