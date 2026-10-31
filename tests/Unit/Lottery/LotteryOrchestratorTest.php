<?php


use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Solvers\TestSolver;

uses()->group('Unit.Lottery');

beforeEach(function () {
    $this->audits = fakeAudits();
});

function auditTypes(): array
{
    return array_count_values(array_column(test()->audits->records, 'type'));
}

describe('LotteryOrchestrator', function () {
    test('perfect match: all groups balanced', function () {
        $manifest = mockManifest(1, [
            // Group 1: 3 participants, 3 items (balanced)
            10 => [
                'participants' => [100 => [200, 201, 202], 101 => [200, 201, 202], 102 => [200, 201, 202]],
                'items'        => [200, 201, 202],
            ],
            // Group 2: 2 participants, 2 items (balanced)
            20 => [
                'participants' => [103 => [203, 204], 104 => [203, 204]],
                'items'        => [203, 204],
            ],
        ]);

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(0);

        // One audit per group (nothing left over for a second chance), one for the project
        expect(auditTypes())->toBe(['group_execution' => 2, 'project_execution' => 1]);
    });

    test('phase 1 only: groups with more items than participants', function () {
        $manifest = mockManifest(
            2,
            [
                // Group 1: 2 participants, 4 items (more items)
                10 => [
                    'participants' => [100 => [200, 201, 202, 203], 101 => [200, 201, 202, 203]],
                    'items'        => [200, 201, 202, 203],
                ],
                // Group 2: 1 participant, 3 items (more items)
                20 => [
                    'participants' => [102 => [204, 205, 206]],
                    'items'        => [204, 205, 206],
                ],
            ]
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Expect 3 picks total (2 + 1), 4 orphan items, 0 orphan participants
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(4); // (202, 203) + (205, 206)
    });

    test('phase 2 only: groups with more participants than items', function () {
        $manifest = mockManifest(3, [
            // Group 1: 4 participants, 2 items (more participants)
            10 => [
                'participants' => [100 => [200, 201], 101 => [200, 201], 102 => [200, 201], 103 => [200, 201]],
                'items'        => [200, 201],
            ],
            // Group 2: 3 participants, 1 item (more participants)
            20 => [
                'participants' => [104 => [202], 105 => [202], 106 => [202]],
                'items'        => [202],
            ],
        ]);

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Expect 3 picks total (2 + 1), 0 orphan items, 4 orphan participants
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['participants'])->toHaveCount(4); // (102, 103) + (105, 106)
        expect($report->orphans['items'])->toHaveCount(0);
    });

    test('mixed phases with second chance distribution', function () {
        $manifest = mockManifest(
            4,
            [
                // Phase 1: 2 participants, 5 items (3 orphan items)
                10 => [
                    'participants' => [100 => [200, 201, 202, 203], 101 => [200, 201, 202, 203]],
                    'items'        => [200, 201, 202, 203, 204],
                ],
                // Phase 2: 5 participants, 2 items (3 orphan participants)
                20 => [
                    'participants' => [
                        102 => [205, 206],
                        103 => [205, 206],
                        104 => [205, 206],
                        105 => [205, 206],
                        106 => [205, 206]
                    ],
                    'items' => [205, 206],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 2 picks, 3 orphan items (202, 203, 204)
        // Phase 2: 2 picks, 3 orphan participants (104, 105, 106)
        // Phase 3 (second chance): 3 picks from orphans (104=>202, 105=>203, 106=>204)
        // Total: 2 + 2 + 3 = 7 picks, 0 orphan participants, 0 orphan items
        expect($report->picks)->toHaveCount(7);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(0);
    });

    test('second chance with unbalanced orphans', function () {
        $manifest = mockManifest(
            5,
            [
                // Phase 1: 1 participant, 4 items (3 orphan items)
                10 => [
                    'participants' => [100 => [200, 201, 202, 203]],
                    'items'        => [200, 201, 202, 203],
                ],
                // Phase 2: 4 participants, 1 item (3 orphan participants)
                20 => [
                    'participants' => [101 => [204], 102 => [204], 103 => [204], 104 => [204]],
                    'items'        => [204],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 1 pick, 3 orphan items (201, 202, 203)
        // Phase 2: 1 pick, 3 orphan participants (102, 103, 104)
        // Phase 3: 3 picks from orphans
        // Total: 1 + 1 + 3 = 5 picks, 0 orphans
        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(0);
    });

    test('second chance with more orphan items than participants', function () {
        $manifest = mockManifest(
            6,
            [
                // Phase 1: 1 participant, 6 items (5 orphan items)
                10 => [
                    'participants' => [100 => [200, 201, 202, 203, 204, 205]],
                    'items'        => [200, 201, 202, 203, 204, 205],
                ],
                // Phase 2: 3 participants, 1 item (2 orphan participants)
                20 => [
                    'participants' => [101 => [206], 102 => [206], 103 => [206]],
                    'items'        => [206],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 1 pick, 5 orphan items (201-205)
        // Phase 2: 1 pick, 2 orphan participants (102, 103)
        // Phase 3: 2 picks, 0 orphan participants, 3 orphan items (203, 204, 205)
        // Total: 1 + 1 + 2 = 4 picks
        expect($report->picks)->toHaveCount(4);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(3);
    });

    test('empty manifest', function () {
        $manifest = mockManifest(7, []);

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(0);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(0);
    });

    test('single group single pair', function () {
        $manifest = mockManifest(
            8,
            [
                10 => [
                    'participants' => [100 => [200]],
                    'items'        => [200],
                ],
            ]
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(1);
        expect($report->orphans['participants'])->toHaveCount(0);
        expect($report->orphans['items'])->toHaveCount(0);
    });
});

describe('LotteryOrchestrator audits', function () {
    test('records each group and the whole project', function () {
        $manifest = mockManifest(1, [
            10 => ['participants' => [100 => [200]], 'items' => [200]],
            20 => ['participants' => [101 => [201]], 'items' => [201]],
        ]);

        orchestrator(new TestSolver())->execute($manifest);

        expect(auditTypes())->toBe(['group_execution' => 2, 'project_execution' => 1]);
    });

    test('lets solver failures through, for the run to record', function () {
        $manifest = mockManifest(1, [
            10 => ['participants' => [100 => [200]], 'items' => [200]],
        ]);

        $solver = Mockery::mock(SolverInterface::class);
        $solver->shouldReceive('execute')->andThrow(new LotteryExecutionException('Test failure'));

        expect(fn () => orchestrator($solver)->execute($manifest))->toThrow(LotteryExecutionException::class);
    });
});
