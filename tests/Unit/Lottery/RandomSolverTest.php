<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\RandomSolver;

uses()->group('Unit.Lottery');

describe('RandomSolver', function () {
    test('balanced lottery', function () {
        $families = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        $units = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($families, $units);
        $result = (new RandomSolver())->execute($spec);

        // Should have 5 picks
        expect($result->picks)->toHaveCount(5);

        // Should have no orphans
        expect($result->orphans['families'])->toHaveCount(0);
        expect($result->orphans['units'])->toHaveCount(0);

        // All family IDs should appear exactly once
        $familyIds = array_keys($result->picks);
        sort($familyIds);
        expect($familyIds)->toBe([1, 2, 3, 4, 5]);

        // All unit IDs should appear exactly once
        $unitIds = array_values($result->picks);
        sort($unitIds);
        expect($unitIds)->toBe([10, 20, 30, 40, 50]);

        // No duplicates in picks
        expect(array_unique($familyIds))->toHaveCount(5);
        expect(array_unique($unitIds))->toHaveCount(5);
    });

    test('more units than families', function () {
        $families = [1 => [], 2 => [], 3 => []];
        $units = [10, 20, 30, 40, 50, 60, 70];

        $spec = new LotterySpec($families, $units);
        $result = (new RandomSolver())->execute($spec);

        // Should have 3 picks (min of families and units)
        expect($result->picks)->toHaveCount(3);

        // Should have 0 orphan families, 4 orphan units
        expect($result->orphans['families'])->toHaveCount(0);
        expect($result->orphans['units'])->toHaveCount(4);

        // All families should be matched
        $familyIds = array_keys($result->picks);
        sort($familyIds);
        expect($familyIds)->toBe([1, 2, 3]);

        // 4 units should remain unmatched
        $assignedUnits = array_values($result->picks);
        $orphanUnits = $result->orphans['units'];
        $allUnits = array_merge($assignedUnits, $orphanUnits);
        sort($allUnits);
        expect($allUnits)->toBe([10, 20, 30, 40, 50, 60, 70]);

        // No overlaps
        expect(array_intersect($assignedUnits, $orphanUnits))->toBeEmpty();
    });

    test('more families than units', function () {
        $families = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => []];
        $units = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($families, $units);
        $result = (new RandomSolver())->execute($spec);

        // Should have 5 picks (min of families and units)
        expect($result->picks)->toHaveCount(5);

        // Should have 3 orphan families, 0 orphan units
        expect($result->orphans['families'])->toHaveCount(3);
        expect($result->orphans['units'])->toHaveCount(0);

        // All units should be matched
        $unitIds = array_values($result->picks);
        sort($unitIds);
        expect($unitIds)->toBe([10, 20, 30, 40, 50]);

        // 3 families should remain unmatched
        $assignedFamilies = array_keys($result->picks);
        $orphanFamilies = $result->orphans['families'];
        $allFamilies = array_merge($assignedFamilies, $orphanFamilies);
        sort($allFamilies);
        expect($allFamilies)->toBe([1, 2, 3, 4, 5, 6, 7, 8]);

        // No overlaps
        expect(array_intersect($assignedFamilies, $orphanFamilies))->toBeEmpty();
    });

    test('no duplicate assignments', function () {
        $families = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => [], 9 => [], 10 => []];
        $units = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20];

        $spec = new LotterySpec($families, $units);

        // Run multiple times to verify randomness and no duplicates
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $result = (new RandomSolver())->execute($spec);
            $results[] = $result->picks;

            // Each family should appear at most once
            $familyIds = array_keys($result->picks);
            expect($familyIds)->toHaveCount(10);
            expect($familyIds)->each->toBeIn(range(1, 10));

            // Each unit should appear at most once
            $unitIds = array_values($result->picks);
            expect($unitIds)->toHaveCount(10);
            expect(array_unique($unitIds))->toHaveCount(10);

            // Verify all families present
            sort($familyIds);
            expect($familyIds)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

            // Verify all units present
            sort($unitIds);
            expect($unitIds)->toBe([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]);
        }

        // Verify we got different orderings (randomness)
        $uniqueResults = array_unique($results, SORT_REGULAR);
        expect(count($uniqueResults))->toBeGreaterThan(1, 'Results should vary due to randomness');
    });

    test('empty input', function () {
        $families = [];
        $units = [];

        $spec = new LotterySpec($families, $units);
        $result = (new RandomSolver())->execute($spec);

        expect($result->picks)->toBe([]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('single family single unit', function () {
        $families = [42 => []];
        $units = [99];

        $spec = new LotterySpec($families, $units);
        $result = (new RandomSolver())->execute($spec);

        expect($result->picks)->toBe([42 => 99]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });
});
