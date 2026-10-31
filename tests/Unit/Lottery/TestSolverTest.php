<?php


use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\TestSolver;

uses()->group('Unit.Lottery');

describe('TestSolver', function () {
    test('balanced deterministic assignment', function () {
        $participants = [1 => [], 3 => [], 5 => []];
        $items = [2, 4, 6];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([1 => 2, 3 => 4, 5 => 6]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('more items deterministic', function () {
        $participants = [10 => [], 20 => []];
        $items = [5, 15, 25, 35];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([10 => 5, 20 => 15]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([25, 35]);
    });

    test('more participants deterministic', function () {
        $participants = [2 => [], 4 => [], 6 => [], 8 => []];
        $items = [1, 3];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([2 => 1, 4 => 3]);
        expect($result->orphans['participants'])->toBe([6, 8]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('single pair', function () {
        $participants = [100 => []];
        $items = [200];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([100 => 200]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('empty input', function () {
        $participants = [];
        $items = [];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new TestSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('maintains deterministic order across calls', function () {
        $participants = [5 => [], 2 => [], 8 => [], 1 => []];
        $items = [30, 10, 20, 40];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        // Execute multiple times
        $result1 = (new TestSolver())->execute($manifest, $spec);
        $result2 = (new TestSolver())->execute($manifest, $spec);
        $result3 = (new TestSolver())->execute($manifest, $spec);

        // Should produce identical picks and orphans (deterministic)
        expect($result1->picks)->toBe($result2->picks);
        expect($result2->picks)->toBe($result3->picks);
        expect($result1->orphans)->toBe($result2->orphans);
        expect($result2->orphans)->toBe($result3->orphans);

        // Verify correct sorting: participants [1,2,5,8], items [10,20,30,40]
        expect($result1->picks)->toBe([1 => 10, 2 => 20, 5 => 30, 8 => 40]);
    });
});
