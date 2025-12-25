<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;

uses()->group('Unit.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
});

describe('Glpk Orchestrator', function () {
    test('distributeUnits with large spec calls HybridDistribution', function () {
        config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 25);
        config()->set('lottery.solvers.glpk.config.timeout', 10);

        // Mock Glpk to capture auditTask calls
        $glpkMock = mockGlpkWithAuditCapture();

        // Large spec (26 families >= 25 threshold)
        $families = [];
        $units = [];
        for ($i = 1; $i <= 26; $i++) {
            $families[$i] = range($i, $i + 25);
            $units[] = $i;
        }

        $spec = new LotterySpec(families: $families, units: $units);

        $result = $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have called auditTask once
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Should have used HybridDistribution
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('hybrid_distribution');

        // Should have distribution
        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result)->toHaveCount(26);
    });

    test('distributeUnits with small spec calls GlpkDistribution', function () {
        config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 25);
        config()->set('lottery.solvers.glpk.config.timeout', 10);
        config()->set('lottery.solvers.glpk.config.glpk_phase1_timeout', 5);

        // Mock Glpk to capture auditTask calls
        $glpkMock = mockGlpkWithAuditCapture();

        // Small spec (3 families < 25 threshold)
        $spec = new LotterySpec(
            families: [1 => [10, 20], 2 => [20, 10], 3 => [10, 20]],
            units: [10, 20, 30]
        );

        $result = $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have called auditTask once
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Should have used GlpkDistribution
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('glpk_distribution');

        // Should have phase1 and phase2 metadata
        expect($taskResult->metadata)->toHaveKey('phase1');
        expect($taskResult->metadata)->toHaveKey('phase2');

        // Should have distribution
        expect($result)->toBeArray();
        expect($result)->toHaveCount(3);
    });

    test('distributeUnits falls back to HybridDistribution on timeout', function () {
        config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 25);
        config()->set('lottery.solvers.glpk.config.timeout', 10);
        config()->set('lottery.solvers.glpk.config.glpk_phase1_timeout', 0.001); // Force timeout

        // Mock Glpk to capture auditTask calls
        $glpkMock = mockGlpkWithAuditCapture();

        // Small spec (would normally use GLPK, but will timeout)
        $spec = new LotterySpec(
            families: [1 => [10, 20], 2 => [20, 10]],
            units: [10, 20]
        );

        $result = $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have called auditTask once (only for successful fallback)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Should have fallen back to HybridDistribution
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('hybrid_distribution');

        // Should have hybrid metadata
        expect($taskResult->metadata)->toHaveKey('iterations');
        expect($taskResult->metadata)->toHaveKey('feasible_steps');

        // Should have distribution
        expect($result)->toBeArray();
        expect($result)->toHaveCount(2);
    });

    test('identifyWorstUnits calls WorstUnitsPruning runner', function () {
        config()->set('lottery.solvers.glpk.config.timeout', 10);

        // Mock Glpk to capture auditTask calls
        $glpkMock = mockGlpkWithAuditCapture();

        // Spec with more units than families
        $spec = new LotterySpec(
            families: [1 => [10, 20, 30, 40, 50], 2 => [20, 30, 10, 40, 50]],
            units: [10, 20, 30, 40, 50]
        );

        $result = $glpkMock->identifyWorstUnits(mockManifest(), $spec);

        // Should have called auditTask once
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Should have used WorstUnitsPruning
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('worst_units_pruning');

        // Should have worst_units array
        expect($result)->toBeArray();
    });
});
