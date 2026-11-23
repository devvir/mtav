<?php


use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\SpecBalancer;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    $this->balancer = app(SpecBalancer::class);
});

describe('SpecBalancer::addMockItems', function () {
    it('adds single mock item for simple imbalance', function () {
        // 3 participants, 2 items: need 1 mock item
        $spec = new LotterySpec(
            participants: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
            ],
            items: [10, 20]
        );

        $result = $this->balancer->addMockItems($spec);

        // Should return balanced spec (3 participants, 3 items)
        expect($result->participantCount())->toBe(3);
        expect($result->itemCount())->toBe(3);
        expect($result->isBalanced())->toBeTrue();

        // Should have 2 real items + 1 mock
        expect($result->items)->toHaveCount(3);
        expect($result->items[0])->toBe(10);
        expect($result->items[1])->toBe(20);
        expect($result->items[2])->toBe('MOCK_1');
    });

    it('adds multiple mock items when needed', function () {
        // 5 participants, 2 items: need 3 mock items
        $spec = new LotterySpec(
            participants: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
                103 => [20, 10],
                104 => [10, 20],
            ],
            items: [10, 20]
        );

        $result = $this->balancer->addMockItems($spec);

        // Should return balanced spec (5 participants, 5 items)
        expect($result->participantCount())->toBe(5);
        expect($result->itemCount())->toBe(5);
        expect($result->isBalanced())->toBeTrue();

        // Should have 2 real items + 3 mock items
        expect($result->items)->toBe([10, 20, 'MOCK_1', 'MOCK_2', 'MOCK_3']);
    });

    it('appends mock items to all participant preferences', function () {
        // Verify mock items are added to end of each participant's preferences
        // 4 participants, 2 items: need 2 mock items
        $spec = new LotterySpec(
            participants: [
                100 => [10, 20],
                101 => [20, 10],
                102 => [10, 20],
                103 => [20, 10],
            ],
            items: [10, 20]
        );

        $result = $this->balancer->addMockItems($spec);

        // Each participant should have original preferences plus mock items at the end
        expect($result->participants[100])->toBe([10, 20, 'MOCK_1', 'MOCK_2']);
        expect($result->participants[101])->toBe([20, 10, 'MOCK_1', 'MOCK_2']);
        expect($result->participants[102])->toBe([10, 20, 'MOCK_1', 'MOCK_2']);
        expect($result->participants[103])->toBe([20, 10, 'MOCK_1', 'MOCK_2']);
    });

    it('handles extreme imbalance', function () {
        // 10 participants, 1 item: need 9 mock items
        $participants = [];
        for ($i = 100; $i < 110; $i++) {
            $participants[$i] = [10];
        }

        $spec = new LotterySpec(participants: $participants, items: [10]);
        $result = $this->balancer->addMockItems($spec);

        // Should return balanced spec (10 participants, 10 items)
        expect($result->participantCount())->toBe(10);
        expect($result->itemCount())->toBe(10);
        expect($result->isBalanced())->toBeTrue();

        // Should have 1 real + 9 mock items
        expect($result->items)->toHaveCount(10);
        expect($result->items[0])->toBe(10);
        for ($i = 1; $i <= 9; $i++) {
            expect($result->items[$i])->toBe("MOCK_{$i}");
        }
    });
});

describe('SpecBalancer::isMockItem', function () {
    it('returns true for mock item IDs', function () {
        expect($this->balancer->isMockItem('MOCK_1'))->toBeTrue();
        expect($this->balancer->isMockItem('MOCK_2'))->toBeTrue();
        expect($this->balancer->isMockItem('MOCK_999'))->toBeTrue();
    });

    it('returns false for real item IDs', function () {
        expect($this->balancer->isMockItem(10))->toBeFalse();
        expect($this->balancer->isMockItem(100))->toBeFalse();
        expect($this->balancer->isMockItem('10'))->toBeFalse();
        expect($this->balancer->isMockItem('100'))->toBeFalse();
    });

    it('returns false for strings that do not start with MOCK_', function () {
        expect($this->balancer->isMockItem('FAKE_1'))->toBeFalse();
        expect($this->balancer->isMockItem('mock_1'))->toBeFalse(); // lowercase
        expect($this->balancer->isMockItem('ITEM_MOCK_1'))->toBeFalse();
        expect($this->balancer->isMockItem(''))->toBeFalse();
    });

    it('handles mixed type inputs correctly', function () {
        // Integer IDs are never mock items
        expect($this->balancer->isMockItem(1))->toBeFalse();
        expect($this->balancer->isMockItem(0))->toBeFalse();

        // String IDs must start with 'MOCK_'
        expect($this->balancer->isMockItem('MOCK_0'))->toBeTrue();
        expect($this->balancer->isMockItem('MOCK_ABC'))->toBeTrue();
    });
});
