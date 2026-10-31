<?php


use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\ItemDistribution;

uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('ItemDistribution', function () {
    test('executes with min_satisfaction context and returns valid TaskResult', function () {
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(ItemDistribution::class);
        $result = $runner
            ->withContext(['min_satisfaction' => 2])
            ->execute($spec, $timeout);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::ITEM_DISTRIBUTION);

        // Check data contains distribution
        expect($result->data)->toHaveKey('distribution');

        $distribution = $result->get('distribution');
        expect($distribution)->toBeArray();
        expect($distribution)->toHaveCount(3);

        // Verify all participants got items
        expect(array_keys($distribution))->toEqualCanonicalizing([1, 2, 3]);
        expect($distribution)->each->toBeIn($items);

        // No duplicate item assignments
        expect(array_unique(array_values($distribution)))->toHaveCount(3);
    });

    test('throws exception when min_satisfaction context is missing', function () {
        $participants = [1 => [10, 20], 2 => [20, 10]];
        $items = [10, 20];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(ItemDistribution::class);

        expect(fn () => $runner->execute($spec, $timeout))
            ->toThrow(InvalidArgumentException::class, 'ItemDistribution requires min_satisfaction in context');
    });

    test('throws exception when min_satisfaction is null', function () {
        $participants = [1 => [10, 20], 2 => [20, 10]];
        $items = [10, 20];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(ItemDistribution::class);

        expect(fn () => $runner->execute($spec, $timeout))
            ->toThrow(InvalidArgumentException::class, 'ItemDistribution requires min_satisfaction in context');
    });

    test('respects min_satisfaction constraint', function () {
        // All participants want item 10 first
        $participants = [
            1 => [10, 20, 30],
            2 => [10, 30, 20],
            3 => [10, 20, 30],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;

        $runner = app(ItemDistribution::class);
        $result = $runner
            ->withContext(['min_satisfaction' => 2])
            ->execute($spec, $timeout);

        $distribution = $result->get('distribution');

        // Calculate worst rank
        $worstRank = 0;
        foreach ($distribution as $participantId => $itemId) {
            $rank = array_search($itemId, $participants[$participantId]) + 1;
            $worstRank = max($worstRank, $rank);
        }

        // No participant should get worse than rank 2
        expect($worstRank)->toBeLessThanOrEqual(2);
    });

    test('includes proper metadata', function () {
        $participants = [1 => [10, 20], 2 => [20, 10]];
        $items = [10, 20];

        $spec = new LotterySpec($participants, $items);
        $timeout = 5;

        $runner = app(ItemDistribution::class);
        $result = $runner
            ->withContext(['min_satisfaction' => 1])
            ->execute($spec, $timeout);

        $metadata = $result->metadata;

        // Should have min_satisfaction from context
        expect($metadata)->toHaveKey('min_satisfaction');
        expect($metadata['min_satisfaction'])->toBe(1);

        // Should have timing info
        expect($metadata)->toHaveKey('time_ms');
        expect($metadata['time_ms'])->toBeNumeric();
        expect($metadata['time_ms'])->toBeGreaterThan(0);

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
        expect($metadata['artifacts'])->toHaveCount(3); // .mod, .dat, .sol
    });

    test('works with different min_satisfaction values', function () {
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $timeout = 10;
        $runner = app(ItemDistribution::class);

        // Test with S=1 (strict)
        $result1 = $runner
            ->withContext(['min_satisfaction' => 1])
            ->execute($spec, $timeout);
        expect($result1->get('distribution'))->toHaveCount(3);

        // Test with S=2 (relaxed)
        $result2 = $runner
            ->withContext(['min_satisfaction' => 2])
            ->execute($spec, $timeout);
        expect($result2->get('distribution'))->toHaveCount(3);

        // Test with S=3 (very relaxed)
        $result3 = $runner
            ->withContext(['min_satisfaction' => 3])
            ->execute($spec, $timeout);
        expect($result3->get('distribution'))->toHaveCount(3);
    });
});
