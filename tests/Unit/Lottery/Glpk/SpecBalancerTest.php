<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\SpecBalancer;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    $this->balancer = app(SpecBalancer::class);
});

describe('SpecBalancer::pruneWorstUnits', function () {
    it('removes least preferred unit from balanced scenario', function () {
        // 3 families, 4 units: unit 40 is universally least preferred
        $spec = new LotterySpec(
            families: [
                100 => [10, 20, 30, 40],
                101 => [20, 30, 10, 40],
                102 => [30, 10, 20, 40],
            ],
            units: [10, 20, 30, 40]
        );

        $manifest = mockManifest(1, [10 => ['families' => $spec->families, 'units' => $spec->units]]);
        $result = $this->balancer->pruneWorstUnits($manifest, $spec);

        // Should return spec with 3 families and 3 units (balanced)
        expect($result->familyCount())->toBe(3);
        expect($result->unitCount())->toBe(3);
        expect($result->isBalanced())->toBeTrue();

        // Unit 40 should be removed
        expect($result->units)->toBe([10, 20, 30]);

        // Families should have same preferences minus unit 40
        expect($result->families[100])->toBe([10, 20, 30]);
        expect($result->families[101])->toBe([20, 30, 10]);
        expect($result->families[102])->toBe([30, 10, 20]);
    });

    it('prunes multiple units when needed', function () {
        // 2 families, 5 units: should remove 3 worst units
        $spec = new LotterySpec(
            families: [
                100 => [10, 20, 30, 40, 50],
                101 => [20, 10, 30, 40, 50],
            ],
            units: [10, 20, 30, 40, 50]
        );

        $manifest = mockManifest(1, [10 => ['families' => $spec->families, 'units' => $spec->units]]);
        $result = $this->balancer->pruneWorstUnits($manifest, $spec);

        // Should return balanced spec (2 families, 2 units)
        expect($result->familyCount())->toBe(2);
        expect($result->unitCount())->toBe(2);
        expect($result->isBalanced())->toBeTrue();

        // Should keep only best 2 units
        expect($result->units)->toHaveCount(2);
    });

    it('removes invalid family preferences (from pruned units)', function () {
        // Verify all original preferences are maintained
        $spec = new LotterySpec(
            families: [
                100 => [10, 20, 30],
                101 => [30, 10, 20],
            ],
            units: [10, 20, 30]
        );

        $manifest = mockManifest(1, [10 => ['families' => $spec->families, 'units' => $spec->units]]);
        $result = $this->balancer->pruneWorstUnits($manifest, $spec);

        // Preferences should be identical
        expect($result->families[100])->toBe([10, 30]);
        expect($result->families[101])->toBe([30, 10]);
    });
});

describe('SpecBalancer::addMockUnits', function () {
    it('adds single mock unit for simple imbalance', function () {
        // 3 families, 2 units: need 1 mock unit
        $spec = new LotterySpec(
            families: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
            ],
            units: [10, 20]
        );

        $result = $this->balancer->addMockUnits($spec);

        // Should return balanced spec (3 families, 3 units)
        expect($result->familyCount())->toBe(3);
        expect($result->unitCount())->toBe(3);
        expect($result->isBalanced())->toBeTrue();

        // Should have 2 real units + 1 mock
        expect($result->units)->toHaveCount(3);
        expect($result->units[0])->toBe(10);
        expect($result->units[1])->toBe(20);
        expect($result->units[2])->toBe('MOCK_1');
    });

    it('adds multiple mock units when needed', function () {
        // 5 families, 2 units: need 3 mock units
        $spec = new LotterySpec(
            families: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
                103 => [20, 10],
                104 => [10, 20],
            ],
            units: [10, 20]
        );

        $result = $this->balancer->addMockUnits($spec);

        // Should return balanced spec (5 families, 5 units)
        expect($result->familyCount())->toBe(5);
        expect($result->unitCount())->toBe(5);
        expect($result->isBalanced())->toBeTrue();

        // Should have 2 real units + 3 mock units
        expect($result->units)->toBe([10, 20, 'MOCK_1', 'MOCK_2', 'MOCK_3']);
    });

    it('appends mock units to all family preferences', function () {
        // Verify mock units are added to end of each family's preferences
        // 4 families, 2 units: need 2 mock units
        $spec = new LotterySpec(
            families: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
                103 => [20, 10],
            ],
            units: [10, 20]
        );

        $result = $this->balancer->addMockUnits($spec);

        // Each family should have original preferences plus mock units at the end
        expect($result->families[100])->toBe([10, 20, 'MOCK_1', 'MOCK_2']);
        expect($result->families[101])->toBe([20, 10, 'MOCK_1', 'MOCK_2']);
        expect($result->families[102])->toBe([10, 20, 'MOCK_1', 'MOCK_2']);
        expect($result->families[103])->toBe([20, 10, 'MOCK_1', 'MOCK_2']);
    });

    it('handles extreme imbalance', function () {
        // 10 families, 1 unit: need 9 mock units
        $families = [];
        for ($i = 100; $i < 110; $i++) {
            $families[$i] = [10];
        }

        $spec = new LotterySpec(families: $families, units: [10]);
        $result = $this->balancer->addMockUnits($spec);

        // Should return balanced spec (10 families, 10 units)
        expect($result->familyCount())->toBe(10);
        expect($result->unitCount())->toBe(10);
        expect($result->isBalanced())->toBeTrue();

        // Should have 1 real + 9 mock units
        expect($result->units)->toHaveCount(10);
        expect($result->units[0])->toBe(10);
        for ($i = 1; $i <= 9; $i++) {
            expect($result->units[$i])->toBe("MOCK_{$i}");
        }
    });
});

describe('SpecBalancer::isMockUnit', function () {
    it('returns true for mock unit IDs', function () {
        expect($this->balancer->isMockUnit('MOCK_1'))->toBeTrue();
        expect($this->balancer->isMockUnit('MOCK_2'))->toBeTrue();
        expect($this->balancer->isMockUnit('MOCK_999'))->toBeTrue();
    });

    it('returns false for real unit IDs', function () {
        expect($this->balancer->isMockUnit(10))->toBeFalse();
        expect($this->balancer->isMockUnit(100))->toBeFalse();
        expect($this->balancer->isMockUnit('10'))->toBeFalse();
        expect($this->balancer->isMockUnit('100'))->toBeFalse();
    });

    it('returns false for strings that do not start with MOCK_', function () {
        expect($this->balancer->isMockUnit('FAKE_1'))->toBeFalse();
        expect($this->balancer->isMockUnit('mock_1'))->toBeFalse(); // lowercase
        expect($this->balancer->isMockUnit('UNIT_MOCK_1'))->toBeFalse();
        expect($this->balancer->isMockUnit(''))->toBeFalse();
    });

    it('handles mixed type inputs correctly', function () {
        // Integer IDs are never mock units
        expect($this->balancer->isMockUnit(1))->toBeFalse();
        expect($this->balancer->isMockUnit(0))->toBeFalse();

        // String IDs must start with 'MOCK_'
        expect($this->balancer->isMockUnit('MOCK_0'))->toBeTrue();
        expect($this->balancer->isMockUnit('MOCK_ABC'))->toBeTrue();
    });
});
