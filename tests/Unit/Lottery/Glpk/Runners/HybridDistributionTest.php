<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\HybridDistribution;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('HybridDistribution', function () {
    test('executes binary search + phase2 distribution and returns valid result', function () {
        // Small problem
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(HybridDistribution::class);
        $result = $runner->execute($spec, $timeout);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::HYBRID_DISTRIBUTION);

        // Check data contains distribution and min_satisfaction
        expect($result->data)->toHaveKey('distribution');
        expect($result->data)->toHaveKey('min_satisfaction');

        $distribution = $result->get('distribution');
        $minSatisfaction = $result->get('min_satisfaction');

        // Should find valid distribution
        expect($distribution)->toBeArray();
        expect($distribution)->toHaveCount(3);
        expect($minSatisfaction)->toBeInt();
        expect($minSatisfaction)->toBeGreaterThanOrEqual(1);
        expect($minSatisfaction)->toBeLessThanOrEqual(3);

        // Verify all families got units
        expect(array_keys($distribution))->toEqualCanonicalizing([1, 2, 3]);
        expect($distribution)->each->toBeIn($units);

        // No duplicate unit assignments
        expect(array_unique(array_values($distribution)))->toHaveCount(3);
    });

    test('finds feasible solution even with larger problem', function () {
        // Create a larger problem
        $families = [];
        $units = range(100, 124); // 25 units

        srand(42); // Deterministic shuffle
        foreach (range(1, 25) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);
        $timeout = 5;

        $runner = app(HybridDistribution::class);
        $result = $runner->execute($spec, $timeout);

        // Check result structure
        expect($result->data)->toHaveKey('distribution');
        expect($result->data)->toHaveKey('min_satisfaction');

        $distribution = $result->get('distribution');

        // Binary search should find a solution
        expect($distribution)->toBeArray();
        expect($distribution)->toHaveCount(25);

        // Verify all families assigned
        expect(array_keys($distribution))->toEqualCanonicalizing(range(1, 25));

        // Verify all units assigned uniquely
        expect(array_values($distribution))->toEqualCanonicalizing($units);
    });

    test('finds correct minimum satisfaction value', function () {
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
        $timeout = 10;

        $runner = app(HybridDistribution::class);
        $result = $runner->execute($spec, $timeout);

        $distribution = $result->get('distribution');
        $minSatisfaction = $result->get('min_satisfaction');

        // Verify correct assignment count
        expect($distribution)->toHaveCount(3);

        // Calculate worst rank
        $worstRank = 0;
        foreach ($distribution as $familyId => $unitId) {
            $rank = array_search($unitId, $families[$familyId]) + 1;
            $worstRank = max($worstRank, $rank);
        }

        // Worst family should get rank 2 (max-min fairness)
        expect($worstRank)->toBe(2);
        expect($minSatisfaction)->toBe(2);
    });

    test('includes proper metadata for hybrid strategy', function () {
        // Small problem
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(HybridDistribution::class);
        $result = $runner->execute($spec, $timeout);

        $metadata = $result->metadata;

        // Should have iterations count
        expect($metadata)->toHaveKey('iterations');
        expect($metadata['iterations'])->toBeInt();
        expect($metadata['iterations'])->toBeGreaterThan(0);

        // Should have timeout info
        expect($metadata)->toHaveKey('step_timeout_ms');
        expect($metadata['step_timeout_ms'])->toBeNumeric();
        expect($metadata)->toHaveKey('timeout_ms');
        expect($metadata['timeout_ms'])->toBeNumeric();

        // Should have timing info
        expect($metadata)->toHaveKey('time_ms');
        expect($metadata['time_ms'])->toBeNumeric();
        expect($metadata['time_ms'])->toBeGreaterThan(0);

        // Should have feasible_steps array with intermediate results
        expect($metadata)->toHaveKey('feasible_steps');
        expect($metadata['feasible_steps'])->toBeArray();
        expect($metadata['feasible_steps'])->not->toBeEmpty();

        // Each step should have distribution and min_satisfaction
        foreach ($metadata['feasible_steps'] as $step) {
            expect($step)->toHaveKey('distribution');
            expect($step)->toHaveKey('min_satisfaction');
        }

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
    });

    test('preserves artifacts from execution', function () {
        // Medium problem
        $families = [];
        $units = range(100, 119); // 20 units

        srand(42); // Deterministic shuffle
        foreach (range(1, 20) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);
        $timeout = 5;

        $runner = app(HybridDistribution::class);
        $result = $runner->execute($spec, $timeout);
        $metadata = $result->metadata;

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
        expect($metadata['artifacts'])->toHaveCount(15);

        // Each artifact should have file path and content
        foreach ($metadata['artifacts'] as $filename => $content) {
            expect($filename)->toBeString();
            expect($content)->toBeString();
            expect($content)->not->toBeEmpty();
        }

        // Should have .mod, .dat, .sol files from intermediate successful (feasible) steps
        $extensions = array_map(
            fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
            array_keys($metadata['artifacts'])
        );

        expect($extensions)->toContain('mod');
        expect($extensions)->toContain('dat');
        expect($extensions)->toContain('sol');
    });
});
