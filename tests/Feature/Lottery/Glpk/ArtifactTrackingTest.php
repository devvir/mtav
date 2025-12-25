<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;

uses()->group('Feature.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    config()->set('queue.default', 'sync');

    config()->set('lottery.default', 'glpk');
    config()->set('lottery.solvers.glpk.config.glpk_phase1_timeout', 30);
    config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 100);
});

describe('GLPK Artifact Tracking', function () {
    test('composite runner audit includes GLPK artifacts with mod, dat, and sol files', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 1 audit call (composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Verify audit has artifacts
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->metadata)->toHaveKey('artifacts');

        $artifacts = $taskResult->metadata['artifacts'];
        expect($artifacts)->toBeArray();
        expect($artifacts)->not->toBeEmpty();

        // Verify artifact structure (filename => content)
        foreach ($artifacts as $filename => $content) {
            expect($filename)->toBeString();
            expect($content)->toBeString();
            expect($content)->not->toBeEmpty();

            // Verify file extensions
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            expect($ext)->toBeIn(['mod', 'dat', 'sol']);
        }

        // Composite runners combine artifacts from both phases (6 files: 3+3)
        expect(count($artifacts))->toBeGreaterThanOrEqual(6);

        // Verify we have multiple instances of each file type
        $extensions = array_map(
            fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
            array_keys($artifacts)
        );
        $extCounts = array_count_values($extensions);

        // Should have at least 2 mod, 2 dat, 2 sol files (phase1 + phase2)
        expect($extCounts['mod'])->toBeGreaterThanOrEqual(2);
        expect($extCounts['dat'])->toBeGreaterThanOrEqual(2);
        expect($extCounts['sol'])->toBeGreaterThanOrEqual(2);
    });

    test('artifact content contains valid GMPL code', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 1 audit call (composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Check artifacts from composite runner
        $artifacts = $glpkMock->auditCalls[0]->metadata['artifacts'];

        $modCount = 0;
        $datCount = 0;
        $solCount = 0;

        foreach ($artifacts as $filename => $content) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if ($ext === 'mod') {
                // Model file should contain GMPL keywords
                expect($content)->toContain('set');
                expect($content)->toContain('param');
                expect($content)->toContain('var');
                expect($content)->toContain('minimize');
                expect($content)->toEndWith('end;');
                $modCount++;
            } elseif ($ext === 'dat') {
                // Data file should contain data section
                expect($content)->toStartWith('data;');
                expect($content)->toContain('end;');
                expect($content)->toContain('set C :=');
                expect($content)->toContain('set V :=');
                $datCount++;
            } elseif ($ext === 'sol') {
                // Solution file should contain GLPK output
                expect($content)->toContain('Problem:');
                expect($content)->toContain('Status:');
                $solCount++;
            }
        }

        // Verify we have multiple files of each type from both phases
        expect($modCount)->toBeGreaterThanOrEqual(2);
        expect($datCount)->toBeGreaterThanOrEqual(2);
        expect($solCount)->toBeGreaterThanOrEqual(2);
    });

    test('artifacts are preserved across multiple task executions', function () {
        $spec = new LotterySpec(
            families: [
                1 => [1, 2, 3],
                2 => [2, 1, 3],
                3 => [3, 1, 2],
            ],
            units: [1, 2, 3]
        );
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 1 audit call (composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Composite runner should have combined artifacts from both phases
        $taskResult = $glpkMock->auditCalls[0];
        $artifacts = $taskResult->metadata['artifacts'];

        // Should have 6 artifacts (3 from phase1 + 3 from phase2)
        expect(count($artifacts))->toBe(6);

        // All artifact filenames should be unique
        $allArtifactNames = array_keys($artifacts);
        expect(count($allArtifactNames))->toBe(count(array_unique($allArtifactNames)));
    });

    test('metadata includes timing information alongside artifacts', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 1 audit call (composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Verify metadata structure
        $taskResult = $glpkMock->auditCalls[0];
        $metadata = $taskResult->metadata;

        // Should have composite timing info
        expect($metadata)->toHaveKey('timeout_ms');
        expect($metadata['timeout_ms'])->toBeNumeric();

        // Should have artifacts
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();

        // Should have phase1 and phase2 metadata objects
        expect($metadata)->toHaveKey('phase1');
        expect($metadata)->toHaveKey('phase2');

        // Each phase should have timing info
        expect($metadata['phase1'])->toHaveKey('time_ms');
        expect($metadata['phase1']['time_ms'])->toBeNumeric();
        expect($metadata['phase1']['time_ms'])->toBeGreaterThan(0);

        expect($metadata['phase2'])->toHaveKey('time_ms');
        expect($metadata['phase2']['time_ms'])->toBeNumeric();
        expect($metadata['phase2']['time_ms'])->toBeGreaterThan(0);
    });

    test('hybrid strategy stores artifacts from binary search phase2', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture(['glpk_phase1_max_size' => 0, 'timeout' => 10]);

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have 1 audit call (HybridDistribution composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        // Should be hybrid_distribution task
        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('hybrid_distribution');

        // Should have artifacts from phase2 GLPK executions
        expect($taskResult->metadata)->toHaveKey('artifacts');
        $artifacts = $taskResult->metadata['artifacts'];
        expect($artifacts)->toBeArray();
        expect($artifacts)->not->toBeEmpty();

        // Verify artifact structure
        foreach ($artifacts as $filename => $content) {
            expect($filename)->toBeString();
            expect($content)->toBeString();
            expect($content)->not->toBeEmpty();

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            expect($ext)->toBeIn(['mod', 'dat', 'sol']);
        }

        // Hybrid has many phase2 executions (binary search iterations)
        // Each iteration creates 3 files, so we should have many artifacts
        expect(count($artifacts))->toBeGreaterThanOrEqual(3);
    });

    test('hybrid strategy metadata shows binary search iterations and feasible steps', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture(['glpk_phase1_max_size' => 0, 'timeout' => 10]);

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have 1 audit call (HybridDistribution composite runner)
        expect(count($glpkMock->auditCalls))->toBe(1);

        $taskResult = $glpkMock->auditCalls[0];
        expect($taskResult->task->value)->toBe('hybrid_distribution');

        $metadata = $taskResult->metadata;

        // Should have iterations count from binary search
        expect($metadata)->toHaveKey('iterations');
        expect($metadata['iterations'])->toBeGreaterThan(0);

        // Should have feasible_steps array with all phase2 executions
        expect($metadata)->toHaveKey('feasible_steps');
        expect($metadata['feasible_steps'])->toBeArray();
        expect($metadata['feasible_steps'])->not->toBeEmpty();

        // Each feasible step should have distribution, min_satisfaction, and time_ms
        foreach ($metadata['feasible_steps'] as $step) {
            expect($step)->toHaveKey('distribution');
            expect($step)->toHaveKey('min_satisfaction');
            expect($step)->toHaveKey('time_ms');
        }

        // Should have timeout info
        expect($metadata)->toHaveKey('timeout_ms');
        expect($metadata['timeout_ms'])->toBeNumeric();

        // Combined artifacts at top level (from final phase2 execution)
        expect($metadata)->toHaveKey('artifacts');
        $artifacts = $metadata['artifacts'];
        expect($artifacts)->toBeArray();
        expect($artifacts)->not->toBeEmpty();

        // Verify all three file types present
        $extensions = array_map(
            fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
            array_keys($artifacts)
        );
        expect($extensions)->toContain('mod');
        expect($extensions)->toContain('dat');
        expect($extensions)->toContain('sol');
    });

    test('timeout in GLPK phase1 triggers fallback to hybrid strategy', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);

        // Force timeout by setting extremely short phase1_timeout
        $glpkMock = mockGlpkWithAuditCapture([
            'glpk_phase1_max_size' => 100, // Don't force hybrid via config
            'glpk_phase1_timeout' => 0.001, // Force timeout
            'timeout' => 10,
        ]);

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have 1 audit call (fallback to HybridDistribution)
        expect(count($glpkMock->auditCalls))->toBe(1);

        $taskResult = $glpkMock->auditCalls[0];

        // Should have fallen back to hybrid_distribution
        expect($taskResult->task->value)->toBe('hybrid_distribution');

        $metadata = $taskResult->metadata;

        // Should have hybrid metadata structure
        expect($metadata)->toHaveKey('iterations');
        expect($metadata['iterations'])->toBeGreaterThan(0);

        expect($metadata)->toHaveKey('feasible_steps');
        expect($metadata['feasible_steps'])->toBeArray();
        expect($metadata['feasible_steps'])->not->toBeEmpty();

        // Should have artifacts from hybrid execution
        expect($metadata)->toHaveKey('artifacts');
        expect($metadata['artifacts'])->toBeArray();
        expect($metadata['artifacts'])->not->toBeEmpty();
    });
});
