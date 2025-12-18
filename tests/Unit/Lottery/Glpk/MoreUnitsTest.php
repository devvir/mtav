<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Glpk;
use App\Services\Lottery\Solvers\GlpkSolver;
use App\Services\Lottery\Solvers\SpecBalancer;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');

    $this->glpk = app(Glpk::class);

    // Expose protected method for testing
    $this->balancer = new class ($this->glpk) extends SpecBalancer {
        public function testCollectTopPreferredUnits(LotterySpec $spec): array
        {
            return $this->collectTopPreferredUnits($spec);
        }
    };

    $this->solver = new GlpkSolver($this->glpk, $this->balancer);
});

describe('findMinimalPreferenceSet', function () {
    it('returns all units when preference set exactly matches family count', function () {
        // 3 families, 3 units: each family ranks all 3 in different orders
        $families = [
            1 => [10, 20, 30], // Family 1: 10, 20, 30
            2 => [20, 10, 30], // Family 2: 20, 10, 30
            3 => [30, 10, 20], // Family 3: 30, 10, 20
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);

        $result = $this->balancer->testCollectTopPreferredUnits($spec);

        // At depth 1: {10, 20, 30} = 3 units = 3 families → exact match
        expect($result)->toBe([10, 20, 30]);
    });

    it('expands preference depth until unit count >= family count', function () {
        // 3 families, 5 units: overlap in top preferences
        $families = [
            1 => [10, 20, 30, 40, 50], // Family 1: 10, 20, ...
            2 => [10, 20, 30, 40, 50], // Family 2: 10, 20, ... (same)
            3 => [10, 30, 40, 20, 50], // Family 3: 10, 30, ...
        ];
        $units = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($families, $units);

        $result = $this->balancer->testCollectTopPreferredUnits($spec);

        // At depth 1: {10} = 1 unit < 3 families
        // At depth 2: {10, 20, 30} = 3 units = 3 families → exact match
        expect($result)->toBe([10, 20, 30]);
    });

    it('handles worst-case scenario where Px approaches 2M-1', function () {
        // 3 families, 5 units: each family has completely different preferences
        $families = [
            1 => [10, 20, 30, 40, 50], // Family 1: 10, 20, 30, 40, 50
            2 => [20, 30, 40, 50, 10], // Family 2: 20, 30, 40, 50, 10
            3 => [30, 40, 50, 10, 20], // Family 3: 30, 40, 50, 10, 20
        ];
        $units = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($families, $units);

        $result = $this->balancer->testCollectTopPreferredUnits($spec);

        // At depth 1: {10, 20, 30} = 3 units = 3 families → exact match
        // (This example doesn't actually reach worst case, but validates the algorithm)
        expect($result)->toBe([10, 20, 30]);
        expect(count($result))->toBe(3); // M = 3
        expect(count($result))->toBeLessThanOrEqual(2 * 3 - 1); // Px ≤ 2M-1 = 5
    });

    it('respects preference hierarchy when collecting units', function () {
        // 2 families, 4 units: verify top preferences collected before lower ones
        $families = [
            1 => [10, 20, 30, 40], // Family 1: 10, 20, 30, 40
            2 => [30, 40, 10, 20], // Family 2: 30, 40, 10, 20
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);

        $result = $this->balancer->testCollectTopPreferredUnits($spec);

        // At depth 1: {10, 30} = 2 units = 2 families → exact match
        expect($result)->toBe([10, 30]);

        // Verify these are top-1 preferences (first in array)
        expect($families[1][0])->toBe(10); // Family 1's top choice
        expect($families[2][0])->toBe(30); // Family 2's top choice
    });

    it('returns units in ascending ID order', function () {
        // 2 families, 3 units: verify output is sorted
        $families = [
            1 => [30, 10, 20], // Family 1: 30, 10, 20
            2 => [20, 30, 10], // Family 2: 20, 30, 10
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);

        $result = $this->balancer->testCollectTopPreferredUnits($spec);

        // At depth 1: {30, 20} = 2 units = 2 families → exact match
        // Should be sorted: [20, 30]
        expect($result)->toBe([20, 30]);
    });
});

describe('executeMoreUnits integration', function () {
    it('returns correct result structure for exact match scenario', function () {
        // 2 families, 2 units: Heuristic finds exact match, skips GLPK pre-phase
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should have assignments for both families
        expect($result->picks)->toHaveCount(2);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);

        // Verify max-min fairness (both should get rank 1)
        expect(array_search($result->picks[1], $families[1]))->toBe(0); // Rank 1 (index 0)
        expect(array_search($result->picks[2], $families[2]))->toBe(0); // Rank 1 (index 0)
    });

    it('discards surplus units maintaining fairness', function () {
        // 3 families, 4 units: units 10, 20, 30 are top choices, 40 is last for all
        $families = [
            1 => [10, 20, 30, 40],
            2 => [20, 30, 10, 40],
            3 => [30, 10, 20, 40],
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);
        $manifest = mockManifest(1, [10 => ['families' => $families, 'units' => $units]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should assign 3 units, orphan 1 unit
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['units'])->toHaveCount(1);

        // Unit 40 (least preferred by all) should be orphaned
        expect($result->orphans['units'])->toBe([40]);

        // All families should get top-3 choices
        foreach ($result->picks as $familyId => $unitId) {
            $rank = array_search($unitId, $families[$familyId]);
            expect($rank)->toBeLessThanOrEqual(2); // Index 0-2 = top 3
        }
    });
});
