<?php


use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\RandomSolver;

uses()->group('Unit.Lottery');

describe('RandomSolver', function () {
    test('balanced lottery', function () {
        $participants = [1 => [], 2 => [], 3 => [], 4 => [], 5 => []];
        $items = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new RandomSolver())->execute($manifest, $spec);

        // Should have 5 picks
        expect($result->picks)->toHaveCount(5);

        // Should have no orphans
        expect($result->orphans['participants'])->toHaveCount(0);
        expect($result->orphans['items'])->toHaveCount(0);

        // All participant IDs should appear exactly once
        $participantIds = array_keys($result->picks);
        sort($participantIds);
        expect($participantIds)->toBe([1, 2, 3, 4, 5]);

        // All item IDs should appear exactly once
        $itemIds = array_values($result->picks);
        sort($itemIds);
        expect($itemIds)->toBe([10, 20, 30, 40, 50]);

        // No duplicates in picks
        expect(array_unique($participantIds))->toHaveCount(5);
        expect(array_unique($itemIds))->toHaveCount(5);
    });

    test('more items than participants', function () {
        $participants = [1 => [], 2 => [], 3 => []];
        $items = [10, 20, 30, 40, 50, 60, 70];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new RandomSolver())->execute($manifest, $spec);

        // Should have 3 picks (min of participants and items)
        expect($result->picks)->toHaveCount(3);

        // Should have 0 orphan participants, 4 orphan items
        expect($result->orphans['participants'])->toHaveCount(0);
        expect($result->orphans['items'])->toHaveCount(4);

        // All participants should be matched
        $participantIds = array_keys($result->picks);
        sort($participantIds);
        expect($participantIds)->toBe([1, 2, 3]);

        // 4 items should remain unmatched
        $assignedItems = array_values($result->picks);
        $orphanItems = $result->orphans['items'];
        $allItems = array_merge($assignedItems, $orphanItems);
        sort($allItems);
        expect($allItems)->toBe([10, 20, 30, 40, 50, 60, 70]);

        // No overlaps
        expect(array_intersect($assignedItems, $orphanItems))->toBeEmpty();
    });

    test('more participants than items', function () {
        $participants = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => []];
        $items = [10, 20, 30, 40, 50];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new RandomSolver())->execute($manifest, $spec);

        // Should have 5 picks (min of participants and items)
        expect($result->picks)->toHaveCount(5);

        // Should have 3 orphan participants, 0 orphan items
        expect($result->orphans['participants'])->toHaveCount(3);
        expect($result->orphans['items'])->toHaveCount(0);

        // All items should be matched
        $itemIds = array_values($result->picks);
        sort($itemIds);
        expect($itemIds)->toBe([10, 20, 30, 40, 50]);

        // 3 participants should remain unmatched
        $assignedParticipants = array_keys($result->picks);
        $orphanParticipants = $result->orphans['participants'];
        $allParticipants = array_merge($assignedParticipants, $orphanParticipants);
        sort($allParticipants);
        expect($allParticipants)->toBe([1, 2, 3, 4, 5, 6, 7, 8]);

        // No overlaps
        expect(array_intersect($assignedParticipants, $orphanParticipants))->toBeEmpty();
    });

    test('no duplicate assignments', function () {
        $participants = [1 => [], 2 => [], 3 => [], 4 => [], 5 => [], 6 => [], 7 => [], 8 => [], 9 => [], 10 => []];
        $items = [11, 12, 13, 14, 15, 16, 17, 18, 19, 20];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        // Run multiple times to verify randomness and no duplicates
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $result = (new RandomSolver())->execute($manifest, $spec);
            $results[] = $result->picks;

            // Each participant should appear at most once
            $participantIds = array_keys($result->picks);
            expect($participantIds)->toHaveCount(10);
            expect($participantIds)->each->toBeIn(range(1, 10));

            // Each item should appear at most once
            $itemIds = array_values($result->picks);
            expect($itemIds)->toHaveCount(10);
            expect(array_unique($itemIds))->toHaveCount(10);

            // Verify all participants present
            sort($participantIds);
            expect($participantIds)->toBe([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);

            // Verify all items present
            sort($itemIds);
            expect($itemIds)->toBe([11, 12, 13, 14, 15, 16, 17, 18, 19, 20]);
        }

        // Verify we got different orderings (randomness)
        $uniqueResults = array_unique($results, SORT_REGULAR);
        expect(count($uniqueResults))->toBeGreaterThan(1, 'Results should vary due to randomness');
    });

    test('empty input', function () {
        $participants = [];
        $items = [];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new RandomSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('single participant single item', function () {
        $participants = [42 => []];
        $items = [99];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = (new RandomSolver())->execute($manifest, $spec);

        expect($result->picks)->toBe([42 => 99]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });
});
