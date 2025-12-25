<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\MinSatisfaction;

uses()->group('lottery', 'stress');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

/**
 * Tests that binary search Phase 1 produces identical minimum satisfaction (S)
 * as direct GLPK Phase 1 optimization.
 *
 * Uses problematic benchmark cases that historically took 5-120+ seconds with
 * direct GLPK, demonstrating binary search correctness on degenerate problems.
 */
describe('binary search equivalence', function () {
    test('direct GLPK and binary search produce identical minimum satisfaction S', function (array $specData) {
        $spec = new LotterySpec(families: $specData['preferences'], units: $specData['units']);

        // Method 1: Direct GLPK Phase 1 (bypass orchestrator, high timeout)
        $minSatisfactionRunner = app(MinSatisfaction::class);
        $directResult = $minSatisfactionRunner->execute($spec, 60);
        expect($directResult->task)->toBe(Task::MIN_SATISFACTION);
        $directS = $directResult->get('min_satisfaction');

        // Method 2: Binary search Phase 1 (force via config)
        $glpkMock = mockGlpkWithAuditCapture([
            'glpk_phase1_max_size' => 0,
            'timeout'              => 10,
        ]);

        $glpkMock->distributeUnits(mockManifest(), $spec);
        $binarySearchS = $glpkMock->auditCalls[0]->get('min_satisfaction');

        // Assert equivalence: both methods must find identical minimum S
        expect($binarySearchS)->toBe($directS);
    })->with('problematic_cases');
});
