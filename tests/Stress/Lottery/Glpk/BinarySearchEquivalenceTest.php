<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\MinSatisfaction;

uses()->group('lottery', 'stress');

beforeEach(function () {
    config()->set('logging.default', 'null');
    fakeAudits();
});

/**
 * Tests that binary search Phase 1 produces identical minimum satisfaction (S)
 * as direct GLPK Phase 1 optimization.
 *
 * Uses twenty fixed size-20 instances from the random benchmark scenario, picked among
 * those direct GLPK Phase 1 took longest on (0.2 to 24 seconds), so that the equivalence
 * is checked where the original method struggled.
 */
describe('binary search equivalence', function () {
    test('direct GLPK and binary search produce identical minimum satisfaction S', function (array $specData) {
        $spec = new LotterySpec(participants: $specData['preferences'], items: $specData['items']);

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

        $glpkMock->distributeItems(mockManifest(), $spec);
        $binarySearchS = $glpkMock->auditCalls[0]->get('min_satisfaction');

        // Assert equivalence: both methods must find identical minimum S
        expect($binarySearchS)->toBe($directS);
    })->with('problematic_cases');
});
