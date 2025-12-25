<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\WorstUnitsPruning;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('WorstUnitsPruning', function () {
    test('executes and returns valid TaskResult', function () {
        // More units than families
        $families = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
            3 => [30, 10, 20, 40],
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(WorstUnitsPruning::class);
        $result = $runner->execute($spec, $timeout);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::WORST_UNITS_PRUNING);

        // Check data contains worst_units
        expect($result->data)->toHaveKey('worst_units');

        $worstUnits = $result->get('worst_units');
        expect($worstUnits)->toBeArray();
    });

    test('identifies least preferred unit for pruning', function () {
        // Unit 40 is last choice for all families
        $families = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
            3 => [30, 10, 20, 40],
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(WorstUnitsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstUnits = $result->get('worst_units');

        // Should identify unit 40 as worst
        expect($worstUnits)->toContain(40);
        expect($worstUnits)->toHaveCount(1); // Only 1 excess unit
    });

    test('handles multiple excess units', function () {
        // 3 families, 5 units (2 excess)
        $families = [
            1 => [10, 20, 30, 40, 50],
            2 => [20, 30, 10, 40, 50],
            3 => [30, 10, 20, 40, 50],
        ];
        $units = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(WorstUnitsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstUnits = $result->get('worst_units');

        // Should identify 2 worst units
        expect($worstUnits)->toHaveCount(2);
        expect($worstUnits)->each->toBeIn($units);
    });

    test('returns empty array when no pruning needed', function () {
        // Balanced: 3 families, 3 units
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(WorstUnitsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstUnits = $result->get('worst_units');

        // No units to prune
        expect($worstUnits)->toBeEmpty();
    });

    test('includes proper metadata', function () {
        $families = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);
        $timeout = 5;

        $runner = app(WorstUnitsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $metadata = $result->metadata;

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
        // Balanced scenario: 3 families, 3 units
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(WorstUnitsPruning::class);

        // Should work with empty context
        $result1 = $runner->execute($spec, $timeout, []);
        expect($result1->get('worst_units'))->toBeEmpty();

        // Should work with arbitrary context
        $result2 = $runner->execute($spec, $timeout, ['foo' => 'bar']);
        expect($result2->get('worst_units'))->toBeEmpty();
    });
});
