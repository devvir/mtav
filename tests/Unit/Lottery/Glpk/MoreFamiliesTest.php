<?php

// Copilot - Pending review

use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    config()->set('lottery.default', 'glpk');
});

describe('Orchestrator with more families than units', function () {
    it('handles single group with orphan families', function () {
        // 1 group: 5 families, 3 units → 3 assigned, 2 orphaned
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201, 202],
                    101 => [201, 200, 202],
                    102 => [202, 200, 201],
                    103 => [200, 202, 201],
                    104 => [201, 202, 200],
                ],
                'units' => [200, 201, 202],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign 3 families, orphan 2 families
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['families'])->toHaveCount(2);
        expect($result->orphans['units'])->toBe([]);

        // Verify ALL families are accounted for
        $allFamilies = array_merge(array_keys($result->picks), $result->orphans['families']);
        sort($allFamilies);
        expect($allFamilies)->toBe([100, 101, 102, 103, 104]);
    });

    it('handles multiple groups with orphan redistribution', function () {
        // Group 1: 3 families, 2 units → 2 assigned, 1 orphan family
        // Group 2: 2 families, 4 units → 2 assigned, 2 orphan units
        // Redistribution: 1 orphan family + 2 orphan units → 1 more assignment, 1 leftover unit
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201],
                    101 => [201, 200],
                    102 => [200, 201],
                ],
                'units' => [200, 201],
            ],
            20 => [
                'families' => [
                    103 => [202, 203, 204, 205],
                    104 => [203, 202, 204, 205],
                ],
                'units' => [202, 203, 204, 205],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign: 2 + 2 + 1 (redistribution) = 5 families total
        expect($result->picks)->toHaveCount(5);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toHaveCount(1); // 1 unit left over

        // Verify ALL families got assignments
        $allFamilies = array_keys($result->picks);
        sort($allFamilies);
        expect($allFamilies)->toBe([100, 101, 102, 103, 104]);
    });

    it('handles extreme imbalance across multiple groups', function () {
        // Group 1: 10 families, 2 units → 2 assigned, 8 orphan families
        // Group 2: 1 family, 8 units → 1 assigned, 7 orphan units
        // Redistribution: 8 orphan families + 7 orphan units → 7 more assignments, 1 orphan family remains
        $families1 = [];
        for ($i = 100; $i <= 109; $i++) {
            $families1[$i] = [200, 201];
        }

        $manifest = mockManifest(1, [
            10 => [
                'families' => $families1,
                'units'    => [200, 201],
            ],
            20 => [
                'families' => [110 => [202, 203, 204, 205, 206, 207, 208, 209]],
                'units'    => [202, 203, 204, 205, 206, 207, 208, 209],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign: 2 + 1 + 7 (redistribution) = 10 families total
        expect($result->picks)->toHaveCount(10);
        expect($result->orphans['families'])->toHaveCount(1);
        expect($result->orphans['units'])->toBe([]);

        // Verify total families accounted for
        $allFamilies = array_merge(array_keys($result->picks), $result->orphans['families']);
        sort($allFamilies);
        expect($allFamilies)->toBe(range(100, 110));
    });

    it('validates no families are lost during redistribution', function () {
        // 3 groups with various imbalances
        // Group 1: 4 families, 2 units
        // Group 2: 3 families, 1 unit
        // Group 3: 1 family, 5 units
        // Total: 8 families, 8 units → all should be assigned
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201],
                    101 => [201, 200],
                    102 => [200, 201],
                    103 => [201, 200],
                ],
                'units' => [200, 201],
            ],
            20 => [
                'families' => [
                    104 => [202],
                    105 => [202],
                    106 => [202],
                ],
                'units' => [202],
            ],
            30 => [
                'families' => [107 => [203, 204, 205, 206, 207]],
                'units'    => [203, 204, 205, 206, 207],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // All 8 families should be assigned
        expect($result->picks)->toHaveCount(8);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);

        // Verify exact family IDs
        $allFamilies = array_keys($result->picks);
        sort($allFamilies);
        expect($allFamilies)->toBe(range(100, 107));
    });
});
