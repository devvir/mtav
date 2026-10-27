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
            // Unit type 1: 3 families, 3 units (balanced)
            10 => [
                'families' => [100 => [200, 201, 202], 101 => [200, 201, 202], 102 => [200, 201, 202]],
                'units'    => [200, 201, 202],
            ],
            // Unit type 2: 2 families, 2 units (balanced)
            20 => [
                'families' => [103 => [203, 204], 104 => [203, 204]],
                'units'    => [203, 204],
            ],
        ]);

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);

        // One audit per unit type (nothing left over for a second chance), one for the project
        expect(auditTypes())->toBe(['group_execution' => 2, 'project_execution' => 1]);
    });

    test('phase 1 only: groups with more units than families', function () {
        $manifest = mockManifest(
            2,
            [
                // Unit type 1: 2 families, 4 units (more units)
                10 => [
                    'families' => [100 => [200, 201, 202, 203], 101 => [200, 201, 202, 203]],
                    'units'    => [200, 201, 202, 203],
                ],
                // Unit type 2: 1 family, 3 units (more units)
                20 => [
                    'families' => [102 => [204, 205, 206]],
                    'units'    => [204, 205, 206],
                ],
            ]
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Expect 3 picks total (2 + 1), 4 orphan units, 0 orphan families
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(4); // (202, 203) + (205, 206)
    });

    test('phase 2 only: groups with more families than units', function () {
        $manifest = mockManifest(3, [
            // Unit type 1: 4 families, 2 units (more families)
            10 => [
                'families' => [100 => [200, 201], 101 => [200, 201], 102 => [200, 201], 103 => [200, 201]],
                'units'    => [200, 201],
            ],
            // Unit type 2: 3 families, 1 unit (more families)
            20 => [
                'families' => [104 => [202], 105 => [202], 106 => [202]],
                'units'    => [202],
            ],
        ]);

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Expect 3 picks total (2 + 1), 0 orphan units, 4 orphan families
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['families'])->toHaveCount(4); // (102, 103) + (105, 106)
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('mixed phases with second chance distribution', function () {
        $manifest = mockManifest(
            4,
            [
                // Phase 1: 2 families, 5 units (3 orphan units)
                10 => [
                    'families' => [100 => [200, 201, 202, 203], 101 => [200, 201, 202, 203]],
                    'units'    => [200, 201, 202, 203, 204],
                ],
                // Phase 2: 5 families, 2 units (3 orphan families)
                20 => [
                    'families' => [
                        102 => [205, 206],
                        103 => [205, 206],
                        104 => [205, 206],
                        105 => [205, 206],
                        106 => [205, 206]
                    ],
                    'units' => [205, 206],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 2 picks, 3 orphan units (202, 203, 204)
        // Phase 2: 2 picks, 3 orphan families (104, 105, 106)
        // Phase 3 (second chance): 3 picks from orphans (104=>202, 105=>203, 106=>204)
        // Total: 2 + 2 + 3 = 7 picks, 0 orphan families, 0 orphan units
        expect($report->picks)->toHaveCount(7);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('second chance with unbalanced orphans', function () {
        $manifest = mockManifest(
            5,
            [
                // Phase 1: 1 family, 4 units (3 orphan units)
                10 => [
                    'families' => [100 => [200, 201, 202, 203]],
                    'units'    => [200, 201, 202, 203],
                ],
                // Phase 2: 4 families, 1 unit (3 orphan families)
                20 => [
                    'families' => [101 => [204], 102 => [204], 103 => [204], 104 => [204]],
                    'units'    => [204],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 1 pick, 3 orphan units (201, 202, 203)
        // Phase 2: 1 pick, 3 orphan families (102, 103, 104)
        // Phase 3: 3 picks from orphans
        // Total: 1 + 1 + 3 = 5 picks, 0 orphans
        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('second chance with more orphan units than families', function () {
        $manifest = mockManifest(
            6,
            [
                // Phase 1: 1 family, 6 units (5 orphan units)
                10 => [
                    'families' => [100 => [200, 201, 202, 203, 204, 205]],
                    'units'    => [200, 201, 202, 203, 204, 205],
                ],
                // Phase 2: 3 families, 1 unit (2 orphan families)
                20 => [
                    'families' => [101 => [206], 102 => [206], 103 => [206]],
                    'units'    => [206],
                ],
            ],
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        // Phase 1: 1 pick, 5 orphan units (201-205)
        // Phase 2: 1 pick, 2 orphan families (102, 103)
        // Phase 3: 2 picks, 0 orphan families, 3 orphan units (203, 204, 205)
        // Total: 1 + 1 + 2 = 4 picks
        expect($report->picks)->toHaveCount(4);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(3);
    });

    test('empty manifest', function () {
        $manifest = mockManifest(7, []);

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(0);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('single group single pair', function () {
        $manifest = mockManifest(
            8,
            [
                10 => [
                    'families' => [100 => [200]],
                    'units'    => [200],
                ],
            ]
        );

        $report = orchestrator(new TestSolver())->execute($manifest);

        expect($report->picks)->toHaveCount(1);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });
});

describe('LotteryOrchestrator audits', function () {
    test('records each group and the whole project', function () {
        $manifest = mockManifest(1, [
            10 => ['families' => [100 => [200]], 'units' => [200]],
            20 => ['families' => [101 => [201]], 'units' => [201]],
        ]);

        orchestrator(new TestSolver())->execute($manifest);

        expect(auditTypes())->toBe(['group_execution' => 2, 'project_execution' => 1]);
    });

    test('lets solver failures through, for the run to record', function () {
        $manifest = mockManifest(1, [
            10 => ['families' => [100 => [200]], 'units' => [200]],
        ]);

        $solver = Mockery::mock(SolverInterface::class);
        $solver->shouldReceive('execute')->andThrow(new LotteryExecutionException('Test failure'));

        expect(fn () => orchestrator($solver)->execute($manifest))->toThrow(LotteryExecutionException::class);
    });
});
