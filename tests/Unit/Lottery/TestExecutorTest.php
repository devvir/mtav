<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Executors\TestExecutor;

uses()->group('Unit.Lottery');

describe('TestExecutor', function () {
    test('balanced deterministic assignment', function () {
        $families = [1 => [], 3 => [], 5 => []];
        $units = [2, 4, 6];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);
        $result = $executor->execute($spec);

        expect($result->picks)->toBe([1 => 2, 3 => 4, 5 => 6]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('more units deterministic', function () {
        $families = [10 => [], 20 => []];
        $units = [5, 15, 25, 35];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);
        $result = $executor->execute($spec);

        expect($result->picks)->toBe([10 => 5, 20 => 15]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([25, 35]);
    });

    test('more families deterministic', function () {
        $families = [2 => [], 4 => [], 6 => [], 8 => []];
        $units = [1, 3];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);
        $result = $executor->execute($spec);

        expect($result->picks)->toBe([2 => 1, 4 => 3]);
        expect($result->orphans['families'])->toBe([6, 8]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('single pair', function () {
        $families = [100 => []];
        $units = [200];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);
        $result = $executor->execute($spec);

        expect($result->picks)->toBe([100 => 200]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('empty input', function () {
        $families = [];
        $units = [];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);
        $result = $executor->execute($spec);

        expect($result->picks)->toBe([]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('maintains deterministic order across calls', function () {
        $families = [5 => [], 2 => [], 8 => [], 1 => []];
        $units = [30, 10, 20, 40];

        $executor = new TestExecutor();
        $spec = new LotterySpec($families, $units);

        // Execute multiple times
        $result1 = $executor->execute($spec);
        $result2 = $executor->execute($spec);
        $result3 = $executor->execute($spec);

        // Should produce identical picks and orphans (deterministic)
        expect($result1->picks)->toBe($result2->picks);
        expect($result2->picks)->toBe($result3->picks);
        expect($result1->orphans)->toBe($result2->orphans);
        expect($result2->orphans)->toBe($result3->orphans);

        // Verify correct sorting: families [1,2,5,8], units [10,20,30,40]
        expect($result1->picks)->toBe([1 => 10, 2 => 20, 5 => 30, 8 => 40]);
    });
});
