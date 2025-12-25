<?php

// Copilot - Pending review

use App\Services\Lottery\LotteryOrchestrator;
use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    config()->set('lottery.default', 'glpk');
});

describe('Orchestrator with more units than families', function () {
    it('handles single group with orphan units', function () {
        // 1 group: 3 families, 5 units → 3 assigned, 2 orphaned units
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201, 202, 203, 204],
                    101 => [201, 200, 202, 203, 204],
                    102 => [202, 200, 201, 203, 204],
                ],
                'units' => [200, 201, 202, 203, 204],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign 3 families, orphan 2 units
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toHaveCount(2);

        // Verify ALL families are assigned
        $allFamilies = array_keys($result->picks);
        sort($allFamilies);
        expect($allFamilies)->toBe([100, 101, 102]);
    });

    it('prunes worst units maintaining fairness', function () {
        // Families prefer first 3 units, unit 40 is universally least preferred
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 202, 200, 203],
                    102 => [202, 200, 201, 203],
                ],
                'units' => [200, 201, 202, 203],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign 3 families, orphan 1 unit
        expect($result->picks)->toHaveCount(3);
        expect($result->orphans['units'])->toHaveCount(1);

        // Unit 203 (least preferred) should be orphaned
        expect($result->orphans['units'])->toBe([203]);

        // All families should get top-3 choices
        foreach ($result->picks as $familyId => $unitId) {
            $families = $manifest->data[10]['families'];
            $rank = array_search($unitId, $families[$familyId]);
            expect($rank)->toBeLessThanOrEqual(2); // Index 0-2 = top 3
        }
    });

    it('handles multiple groups with orphan redistribution', function () {
        // Group 1: 2 families, 4 units → 2 assigned, 2 orphan units
        // Group 2: 4 families, 2 units → 2 assigned, 2 orphan families
        // Redistribution: 2 orphan families + 2 orphan units → 2 more assignments
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 200, 202, 203],
                ],
                'units' => [200, 201, 202, 203],
            ],
            20 => [
                'families' => [
                    102 => [204, 205],
                    103 => [205, 204],
                    104 => [204, 205],
                    105 => [205, 204],
                ],
                'units' => [204, 205],
            ],
        ]);

        $orchestrator = LotteryOrchestrator::make(app(GlpkSolver::class), $manifest);
        $result = $orchestrator->execute();

        // Should assign: 2 + 2 + 2 (redistribution) = 6 families total
        expect($result->picks)->toHaveCount(6);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);

        // Verify ALL families got assignments
        $allFamilies = array_keys($result->picks);
        sort($allFamilies);
        expect($allFamilies)->toBe([100, 101, 102, 103, 104, 105]);
    });

    it('validates no units are lost during redistribution', function () {
        // 3 groups with various imbalances
        // Group 1: 2 families, 4 units → 2 orphan units
        // Group 2: 1 family, 3 units → 2 orphan units
        // Group 3: 5 families, 1 unit → 4 orphan families
        // Redistribution: 4 orphan families + 4 orphan units → all matched
        $manifest = mockManifest(1, [
            10 => [
                'families' => [
                    100 => [200, 201, 202, 203],
                    101 => [201, 200, 202, 203],
                ],
                'units' => [200, 201, 202, 203],
            ],
            20 => [
                'families' => [102 => [204, 205, 206]],
                'units'    => [204, 205, 206],
            ],
            30 => [
                'families' => [
                    103 => [207],
                    104 => [207],
                    105 => [207],
                    106 => [207],
                    107 => [207],
                ],
                'units' => [207],
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
