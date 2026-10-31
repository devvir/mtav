<?php


use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\WorstItemsPruning;

uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('WorstItemsPruning', function () {
    test('executes and returns valid TaskResult', function () {
        // More items than participants
        $participants = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
            3 => [30, 10, 20, 40],
        ];
        $items = [10, 20, 30, 40];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(WorstItemsPruning::class);
        $result = $runner->execute($spec, $timeout);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::WORST_ITEMS_PRUNING);

        // Check data contains worst_items
        expect($result->data)->toHaveKey('worst_items');

        $worstItems = $result->get('worst_items');
        expect($worstItems)->toBeArray();
    });

    test('identifies least preferred item for pruning', function () {
        // Item 40 is last choice for all participants
        $participants = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
            3 => [30, 10, 20, 40],
        ];
        $items = [10, 20, 30, 40];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(WorstItemsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstItems = $result->get('worst_items');

        // Should identify item 40 as worst
        expect($worstItems)->toContain(40);
        expect($worstItems)->toHaveCount(1); // Only 1 excess item
    });

    test('handles multiple excess items', function () {
        // 3 participants, 5 items (2 excess)
        $participants = [
            1 => [10, 20, 30, 40, 50],
            2 => [20, 30, 10, 40, 50],
            3 => [30, 10, 20, 40, 50],
        ];
        $items = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(WorstItemsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstItems = $result->get('worst_items');

        // Should identify 2 worst items
        expect($worstItems)->toHaveCount(2);
        expect($worstItems)->each->toBeIn($items);
    });

    test('returns empty array when no pruning needed', function () {
        // Balanced: 3 participants, 3 items
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(WorstItemsPruning::class);
        $result = $runner->execute($spec, $timeout);

        $worstItems = $result->get('worst_items');

        // No items to prune
        expect($worstItems)->toBeEmpty();
    });

    test('includes proper metadata', function () {
        $participants = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
        ];
        $items = [10, 20, 30, 40];

        $spec = new LotterySpec($participants, $items);
        $timeout = 5;

        $runner = app(WorstItemsPruning::class);
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
        // Balanced scenario: 3 participants, 3 items
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(WorstItemsPruning::class);

        // Should work with empty context
        $result1 = $runner->execute($spec, $timeout, []);
        expect($result1->get('worst_items'))->toBeEmpty();

        // Should work with arbitrary context
        $result2 = $runner->execute($spec, $timeout, ['foo' => 'bar']);
        expect($result2->get('worst_items'))->toBeEmpty();
    });
});
