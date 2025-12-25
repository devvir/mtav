<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\MinSatisfaction;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('MinSatisfaction', function () {
    test('executes and returns valid TaskResult', function () {
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(MinSatisfaction::class);
        $result = $runner->execute($spec, $timeout);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::MIN_SATISFACTION);

        // Check data contains min_satisfaction
        expect($result->data)->toHaveKey('min_satisfaction');

        $minSatisfaction = $result->get('min_satisfaction');
        expect($minSatisfaction)->toBeInt();
        expect($minSatisfaction)->toBeGreaterThanOrEqual(1);
        expect($minSatisfaction)->toBeLessThanOrEqual(3);
    });

    test('finds correct min_satisfaction for contention scenario', function () {
        // All families want same unit first
        $families = [
            1 => [10, 20, 30],
            2 => [10, 30, 20],
            3 => [10, 20, 30],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(MinSatisfaction::class);
        $result = $runner->execute($spec, $timeout);

        // One family gets unit 10 (rank 1), others get rank 2
        expect($result->get('min_satisfaction'))->toBe(2);
    });

    test('handles perfect match scenario', function () {
        // Each family gets their first choice
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(MinSatisfaction::class);
        $result = $runner->execute($spec, $timeout);

        expect($result->get('min_satisfaction'))->toBe(1);
    });

    test('includes proper metadata', function () {
        $families = [1 => [10, 20], 2 => [20, 10]];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 5;

        $runner = app(MinSatisfaction::class);
        $result = $runner->execute($spec, $timeout);

        $metadata = $result->metadata;

        // Should have timeout info
        expect($metadata)->toHaveKey('timeout_ms');
        expect($metadata['timeout_ms'])->toBeNumeric();

        // Should have timing info
        expect($metadata)->toHaveKey('time_ms');
        expect($metadata['time_ms'])->toBeNumeric();
        expect($metadata['time_ms'])->toBeGreaterThan(0);

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
        expect($metadata['artifacts'])->toHaveCount(3); // .mod, .dat, .sol
    });

    test('accepts context parameter but does not use it', function () {
        $families = [1 => [10]];
        $units = [10];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(MinSatisfaction::class);

        // Should work with empty context
        $result1 = $runner->execute($spec, $timeout, []);
        expect($result1->get('min_satisfaction'))->toBe(1);

        // Should work with arbitrary context
        $result2 = $runner->execute($spec, $timeout, ['foo' => 'bar']);
        expect($result2->get('min_satisfaction'))->toBe(1);
    });
});
