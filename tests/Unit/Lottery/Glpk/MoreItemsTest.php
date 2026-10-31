<?php


use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
    fakeAudits();
});

describe('Orchestrator with more items than participants', function () {
    it('handles single group with orphan items', function () {
        // 1 group: 3 participants, 5 items → 3 assigned, 2 orphaned items
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201, 202, 203, 204],
                    101 => [201, 200, 202, 203, 204],
                    102 => [202, 200, 201, 203, 204],
                ],
                'items' => [200, 201, 202, 203, 204],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign 3 participants, orphan 2 items
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toHaveCount(2);

        // Verify ALL participants are assigned
        $allParticipants = array_keys($result->picks);
        sort($allParticipants);
        expect($allParticipants)->toBe([100, 101, 102]);
    });

    it('prunes worst items maintaining fairness', function () {
        // Participants prefer first 3 items, item 40 is universally least preferred
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 202, 200, 203],
                    102 => [202, 200, 201, 203],
                ],
                'items' => [200, 201, 202, 203],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign 3 participants, orphan 1 item
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['items'])->toHaveCount(1);

        // Item 203 (least preferred) should be orphaned
        expect($result->orphans['items'])->toBe([203]);

        // All participants should get top-3 choices
        foreach ($result->picks as $participantId => $itemId) {
            $participants = $manifest->data[10]['participants'];
            $rank = array_search($itemId, $participants[$participantId]);
            expect($rank)->toBeLessThanOrEqual(2); // Index 0-2 = top 3
        }
    });

    it('handles multiple groups with orphan redistribution', function () {
        // Group 1: 2 participants, 4 items → 2 assigned, 2 orphan items
        // Group 2: 4 participants, 2 items → 2 assigned, 2 orphan participants
        // Redistribution: 2 orphan participants + 2 orphan items → 2 more assignments
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 200, 202, 203],
                ],
                'items' => [200, 201, 202, 203],
            ],
            20 => [
                'participants' => [
                    102 => [204, 205],
                    103 => [205, 204],
                    104 => [204, 205],
                    105 => [205, 204],
                ],
                'items' => [204, 205],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign: 2 + 2 + 2 (redistribution) = 6 participants total
        expect($result->picks)->toHaveCount(6);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);

        // Verify ALL participants got assignments
        $allParticipants = array_keys($result->picks);
        sort($allParticipants);
        expect($allParticipants)->toBe([100, 101, 102, 103, 104, 105]);
    });

    it('validates no items are lost during redistribution', function () {
        // 3 groups with various imbalances
        // Group 1: 2 participants, 4 items → 2 orphan items
        // Group 2: 1 participant, 3 items → 2 orphan items
        // Group 3: 5 participants, 1 item → 4 orphan participants
        // Redistribution: 4 orphan participants + 4 orphan items → all matched
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 200, 202, 203],
                ],
                'items' => [200, 201, 202, 203],
            ],
            20 => [
                'participants' => [102 => [204, 205, 206]],
                'items'        => [204, 205, 206],
            ],
            30 => [
                'participants' => [
                    103 => [207],
                    104 => [207],
                    105 => [207],
                    106 => [207],
                    107 => [207],
                ],
                'items' => [207],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // All 8 participants should be assigned
        expect($result->picks)->toHaveCount(8);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);

        // Verify exact participant IDs
        $allParticipants = array_keys($result->picks);
        sort($allParticipants);
        expect($allParticipants)->toBe(range(100, 107));
    });
});
