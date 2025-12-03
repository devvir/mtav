<?php

// Copilot - Pending review

use App\Services\Lottery\Solvers\TestSolver;
use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\DataObjects\LotteryManifest;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

uses()->group('Unit.Lottery');

beforeEach(function () {
    Event::fake();
});

// Helper to create manifest for testing
function createManifest(int $projectId, array $data, int $lotteryId = 1): LotteryManifest
{
    $manifest = new class ($projectId, $lotteryId, $data) extends LotteryManifest {
        public function __construct(int $projectId, int $lotteryId, array $data)
        {
            $this->uuid = Str::uuid()->toString();
            $this->projectId = $projectId;
            $this->lotteryId = $lotteryId;
            $this->data = $data;
        }
    };

    return $manifest;
}

describe('LotteryOrchestrator', function () {
    test('perfect match: all groups balanced', function () {
        $manifest = createManifest(1, [
            // Unit type 1: 3 families, 3 units (balanced)
            10 => [
                'families' => [100 => [], 101 => [], 102 => []],
                'units'    => [200, 201, 202],
            ],
            // Unit type 2: 2 families, 2 units (balanced)
            20 => [
                'families' => [103 => [], 104 => []],
                'units'    => [203, 204],
            ],
        ]);

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
        $report = $orchestrator->execute();

        expect($report->picks)->toHaveCount(5);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);

        // Assert events were dispatched: 2 unit types + 1 second-chance + 1 final
        Event::assertDispatched(\App\Events\Lottery\GroupLotteryExecuted::class, 3);
        Event::assertDispatched(\App\Events\Lottery\ProjectLotteryExecuted::class, 1);
    });

    test('phase 1 only: groups with more units than families', function () {
        $manifest = createManifest(
            2,
            [
                // Unit type 1: 2 families, 4 units (more units)
                10 => [
                    'families' => [100 => [], 101 => []],
                    'units'    => [200, 201, 202, 203],
                ],
                // Unit type 2: 1 family, 3 units (more units)
                20 => [
                    'families' => [102 => []],
                    'units'    => [204, 205, 206],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
        $report = $orchestrator->execute();

        // Expect 3 picks total (2 + 1), 4 orphan units, 0 orphan families
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(4); // (202, 203) + (205, 206)
    });

    test('phase 2 only: groups with more families than units', function () {
        $manifest = createManifest(
            3,
            [
                // Unit type 1: 4 families, 2 units (more families)
                10 => [
                    'families' => [100 => [], 101 => [], 102 => [], 103 => []],
                    'units'    => [200, 201],
                ],
                // Unit type 2: 3 families, 1 unit (more families)
                20 => [
                    'families' => [104 => [], 105 => [], 106 => []],
                    'units'    => [202],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
        $report = $orchestrator->execute();

        // Expect 3 picks total (2 + 1), 0 orphan units, 4 orphan families
        expect($report->picks)->toHaveCount(3);
        expect($report->orphans['families'])->toHaveCount(4); // (102, 103) + (105, 106)
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('mixed phases with second chance distribution', function () {
        $manifest = createManifest(
            4,
            [
                // Phase 1: 2 families, 5 units (3 orphan units)
                10 => [
                    'families' => [100 => [], 101 => []],
                    'units'    => [200, 201, 202, 203, 204],
                ],
                // Phase 2: 5 families, 2 units (3 orphan families)
                20 => [
                    'families' => [102 => [], 103 => [], 104 => [], 105 => [], 106 => []],
                    'units'    => [205, 206],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
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
        $manifest = createManifest(
            5,
            [
                // Phase 1: 1 family, 4 units (3 orphan units)
                10 => [
                    'families' => [100 => []],
                    'units'    => [200, 201, 202, 203],
                ],
                // Phase 2: 4 families, 1 unit (3 orphan families)
                20 => [
                    'families' => [101 => [], 102 => [], 103 => [], 104 => []],
                    'units'    => [204],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
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
        $manifest = createManifest(
            6,
            [
                // Phase 1: 1 family, 6 units (5 orphan units)
                10 => [
                    'families' => [100 => []],
                    'units'    => [200, 201, 202, 203, 204, 205],
                ],
                // Phase 2: 3 families, 1 unit (2 orphan families)
                20 => [
                    'families' => [101 => [], 102 => [], 103 => []],
                    'units'    => [206],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
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
        $manifest = createManifest(7, []);

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
        $report = $orchestrator->execute();

        expect($report->picks)->toHaveCount(0);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });

    test('single group single pair', function () {
        $manifest = createManifest(
            8,
            [
                10 => [
                    'families' => [100 => []],
                    'units'    => [200],
                ],
            ]
        );

        $solver = new TestSolver();
        $orchestrator = LotteryOrchestrator::make($solver, $manifest);
        $report = $orchestrator->execute();

        expect($report->picks)->toHaveCount(1);
        expect($report->orphans['families'])->toHaveCount(0);
        expect($report->orphans['units'])->toHaveCount(0);
    });
});
