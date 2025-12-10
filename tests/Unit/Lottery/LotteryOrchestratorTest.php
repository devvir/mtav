<?php

// Copilot - Pending review

use App\Events\Lottery\GroupLotteryExecuted;
use App\Events\Lottery\ProjectLotteryExecuted;
use App\Models\LotteryAudit;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\ExecutionService;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\Solvers\TestSolver;
use Illuminate\Support\Facades\Event;

uses()->group('Unit.Lottery');

beforeEach(function () {
    Event::fake();
    config()->set('lottery.default', 'test');
});

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);

        // Assert events were dispatched: 2 unit types + 1 second-chance + 1 final
        Event::assertDispatched(\App\Events\Lottery\GroupLotteryExecuted::class, 2);
        Event::assertDispatched(\App\Events\Lottery\ProjectLotteryExecuted::class, 1);
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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

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

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $report = $orchestrator->execute();

        expect($report->picks)->toHaveCount(1);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });
});

describe('LotteryOrchestrator Audit Integration', function () {
    test('dispatches group lottery events for audit listeners', function () {
        $manifest = mockManifest(1, [
            10 => ['families' => [100 => [200]], 'units' => [200]],
            20 => ['families' => [101 => [201]], 'units' => [201]],
        ]);

        $orchestrator = LotteryOrchestrator::make(new TestSolver(), $manifest);
        $orchestrator->execute();

        // 2 groups + 1 second-chance = 3 group events, 1 project event
        Event::assertDispatched(GroupLotteryExecuted::class, 2);
        Event::assertDispatched(ProjectLotteryExecuted::class, 1);
    });

    test('audit service receives exception call on failure', function () {
        $manifest = mockManifest(1, [
            10 => ['families' => [100 => [200]], 'units' => [200]],
        ]);

        $solverMock = Mockery::mock(SolverInterface::class);
        $solverMock->shouldReceive('execute')->andThrow(new LotteryExecutionException('Test failure'));

        $auditServiceMock = Mockery::mock(AuditService::class);
        $auditServiceMock->shouldReceive('exception')
            ->once()
            ->with(
                Mockery::on(fn ($m) => $m->uuid === $manifest->uuid),
                'execution_error',
                Mockery::type(LotteryExecutionException::class)
            )
            ->andReturn(new LotteryAudit());

        $executionServiceMock = Mockery::mock(ExecutionService::class);
        $executionServiceMock->shouldReceive('cancelExecutionReservation')->once();

        $orchestrator = new LotteryOrchestrator($solverMock, $manifest, $auditServiceMock, $executionServiceMock);
        $result = $orchestrator->execute();

        expect($result->picks)->toBeEmpty();
    });
});
