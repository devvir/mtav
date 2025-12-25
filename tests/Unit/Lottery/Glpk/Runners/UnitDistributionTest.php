<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\UnitDistribution;


uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('UnitDistribution', function () {
    test('executes with min_satisfaction context and returns valid TaskResult', function () {
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(UnitDistribution::class);
        $result = $runner->execute($spec, $timeout, ['min_satisfaction' => 2]);

        // Check TaskResult structure
        expect($result->task)->toBe(Task::UNIT_DISTRIBUTION);

        // Check data contains distribution
        expect($result->data)->toHaveKey('distribution');

        $distribution = $result->get('distribution');
        expect($distribution)->toBeArray();
        expect($distribution)->toHaveCount(3);

        // Verify all families got units
        expect(array_keys($distribution))->toEqualCanonicalizing([1, 2, 3]);
        expect($distribution)->each->toBeIn($units);

        // No duplicate unit assignments
        expect(array_unique(array_values($distribution)))->toHaveCount(3);
    });

    test('throws exception when min_satisfaction context is missing', function () {
        $families = [1 => [10, 20], 2 => [20, 10]];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(UnitDistribution::class);

        expect(fn () => $runner->execute($spec, $timeout, []))
            ->toThrow(InvalidArgumentException::class, 'UnitDistribution requires min_satisfaction in context');
    });

    test('throws exception when min_satisfaction is null', function () {
        $families = [1 => [10, 20], 2 => [20, 10]];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(UnitDistribution::class);

        expect(fn () => $runner->execute($spec, $timeout, ['min_satisfaction' => null]))
            ->toThrow(InvalidArgumentException::class, 'UnitDistribution requires min_satisfaction in context');
    });

    test('respects min_satisfaction constraint', function () {
        // All families want unit 10 first
        $families = [
            1 => [10, 20, 30],
            2 => [10, 30, 20],
            3 => [10, 20, 30],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;

        $runner = app(UnitDistribution::class);
        $result = $runner->execute($spec, $timeout, ['min_satisfaction' => 2]);

        $distribution = $result->get('distribution');

        // Calculate worst rank
        $worstRank = 0;
        foreach ($distribution as $familyId => $unitId) {
            $rank = array_search($unitId, $families[$familyId]) + 1;
            $worstRank = max($worstRank, $rank);
        }

        // No family should get worse than rank 2
        expect($worstRank)->toBeLessThanOrEqual(2);
    });

    test('includes proper metadata', function () {
        $families = [1 => [10, 20], 2 => [20, 10]];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $timeout = 5;

        $runner = app(UnitDistribution::class);
        $result = $runner->execute($spec, $timeout, ['min_satisfaction' => 1]);

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
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $timeout = 10;
        $runner = app(UnitDistribution::class);

        // Test with S=1 (strict)
        $result1 = $runner->execute($spec, $timeout, ['min_satisfaction' => 1]);
        expect($result1->get('distribution'))->toHaveCount(3);

        // Test with S=2 (relaxed)
        $result2 = $runner->execute($spec, $timeout, ['min_satisfaction' => 2]);
        expect($result2->get('distribution'))->toHaveCount(3);

        // Test with S=3 (very relaxed)
        $result3 = $runner->execute($spec, $timeout, ['min_satisfaction' => 3]);
        expect($result3->get('distribution'))->toHaveCount(3);
    });
});
