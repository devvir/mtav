<?php


use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
    fakeAudits();
});

describe('Orchestrator with more participants than items', function () {
    it('handles single group with orphan participants', function () {
        // 1 group: 5 participants, 3 items → 3 assigned, 2 orphaned
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201, 202],
                    101 => [201, 200, 202],
                    102 => [202, 200, 201],
                    103 => [200, 202, 201],
                    104 => [201, 202, 200],
                ],
                'items' => [200, 201, 202],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign 3 participants, orphan 2 participants
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['participants'])->toHaveCount(2);
        expect($result->orphans['items'])->toBe([]);

        // Verify ALL participants are accounted for
        $allParticipants = array_merge(array_keys($result->picks), $result->orphans['participants']);
        sort($allParticipants);
        expect($allParticipants)->toBe([100, 101, 102, 103, 104]);
    });

    it('handles multiple groups with orphan redistribution', function () {
        // Group 1: 3 participants, 2 items → 2 assigned, 1 orphan participant
        // Group 2: 2 participants, 4 items → 2 assigned, 2 orphan items
        // Redistribution: 1 orphan participant + 2 orphan items → 1 more assignment, 1 leftover item
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201],
                    101 => [201, 200],
                    102 => [200, 201],
                ],
                'items' => [200, 201],
            ],
            20 => [
                'participants' => [
                    103 => [202, 203, 204, 205],
                    104 => [203, 202, 204, 205],
                ],
                'items' => [202, 203, 204, 205],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign: 2 + 2 + 1 (redistribution) = 5 participants total
        expect($result->picks)->toHaveCount(5);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toHaveCount(1); // 1 item left over

        // Verify ALL participants got assignments
        $allParticipants = array_keys($result->picks);
        sort($allParticipants);
        expect($allParticipants)->toBe([100, 101, 102, 103, 104]);
    });

    it('handles extreme imbalance across multiple groups', function () {
        // Group 1: 10 participants, 2 items → 2 assigned, 8 orphan participants
        // Group 2: 1 participant, 8 items → 1 assigned, 7 orphan items
        // Redistribution: 8 orphan participants + 7 orphan items → 7 more assignments, 1 orphan participant remains
        $participants1 = [];
        for ($i = 100; $i <= 109; $i++) {
            $participants1[$i] = [200, 201];
        }

        $manifest = mockManifest(1, [
            10 => [
                'participants' => $participants1,
                'items'        => [200, 201],
            ],
            20 => [
                'participants' => [110 => [202, 203, 204, 205, 206, 207, 208, 209]],
                'items'        => [202, 203, 204, 205, 206, 207, 208, 209],
            ],
        ]);

        $result = orchestrator(app(GlpkSolver::class))->execute($manifest);

        // Should assign: 2 + 1 + 7 (redistribution) = 10 participants total
        expect($result->picks)->toHaveCount(10);
        expect($result->orphans['participants'])->toHaveCount(1);
        expect($result->orphans['items'])->toBe([]);

        // Verify total participants accounted for
        $allParticipants = array_merge(array_keys($result->picks), $result->orphans['participants']);
        sort($allParticipants);
        expect($allParticipants)->toBe(range(100, 110));
    });

    it('validates no participants are lost during redistribution', function () {
        // 3 groups with various imbalances
        // Group 1: 4 participants, 2 items
        // Group 2: 3 participants, 1 item
        // Group 3: 1 participant, 5 items
        // Total: 8 participants, 8 items → all should be assigned
        $manifest = mockManifest(1, [
            10 => [
                'participants' => [
                    100 => [200, 201],
                    101 => [201, 200],
                    102 => [200, 201],
                    103 => [201, 200],
                ],
                'items' => [200, 201],
            ],
            20 => [
                'participants' => [
                    104 => [202],
                    105 => [202],
                    106 => [202],
                ],
                'items' => [202],
            ],
            30 => [
                'participants' => [107 => [203, 204, 205, 206, 207]],
                'items'        => [203, 204, 205, 206, 207],
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
