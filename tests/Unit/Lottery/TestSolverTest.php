<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\TestSolver;

uses()->group('Unit.Lottery');

describe('TestSolver', function () {
    test('balanced deterministic assignment', function () {
        $families = [1 => [], 3 => [], 5 => []];
        $units = [2, 4, 6];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([1 => 2, 3 => 4, 5 => 6]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('more units deterministic', function () {
        $families = [10 => [], 20 => []];
        $units = [5, 15, 25, 35];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([10 => 5, 20 => 15]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([25, 35]);
    });

    test('more families deterministic', function () {
        $families = [2 => [], 4 => [], 6 => [], 8 => []];
        $units = [1, 3];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([2 => 1, 4 => 3]);
        expect($result->orphans['families'])->toBe([6, 8]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('single pair', function () {
        $families = [100 => []];
        $units = [200];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([100 => 200]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('empty input', function () {
        $families = [];
        $units = [];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('maintains deterministic order across calls', function () {
        $families = [5 => [], 2 => [], 8 => [], 1 => []];
        $units = [30, 10, 20, 40];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);

        // Execute multiple times
        $result1 = (new TestSolver())->execute($manifest, $spec);
        $result2 = (new TestSolver())->execute($manifest, $spec);
        $result3 = (new TestSolver())->execute($manifest, $spec);

        // Should produce identical picks and orphans (deterministic)
        expect($result1->picks)->toBe($result2->picks);
        expect($result2->picks)->toBe($result3->picks);
        expect($result1->orphans)->toBe($result2->orphans);
        expect($result2->orphans)->toBe($result3->orphans);

        // Verify correct sorting: families [1,2,5,8], units [10,20,30,40]
        expect($result1->picks)->toBe([1 => 10, 2 => 20, 5 => 30, 8 => 40]);
    });
});
