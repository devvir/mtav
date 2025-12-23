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
    test('task audits include GLPK artifacts with mod, dat, and sol files', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 2 audit calls: Phase 1 + Phase 2
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Verify each audit has artifacts
        foreach ($glpkMock->auditCalls as $taskResult) {
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

            // Verify we have all three file types
            $extensions = array_map(
                fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
                array_keys($artifacts)
            );
            expect($extensions)->toContain('mod');
            expect($extensions)->toContain('dat');
            expect($extensions)->toContain('sol');
        }
    });

    test('artifact content contains valid GMPL code', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 2 audit calls: Phase 1 + Phase 2
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Check first audit's artifacts
        $artifacts = $glpkMock->auditCalls[0]->metadata['artifacts'];

        foreach ($artifacts as $filename => $content) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            if ($ext === 'mod') {
                // Model file should contain GMPL keywords
                expect($content)->toContain('set');
                expect($content)->toContain('param');
                expect($content)->toContain('var');
                expect($content)->toContain('minimize');
                expect($content)->toEndWith('end;');
            } elseif ($ext === 'dat') {
                // Data file should contain data section
                expect($content)->toStartWith('data;');
                expect($content)->toEndWith('end;');
                expect($content)->toContain('set C :=');
                expect($content)->toContain('set V :=');
            } elseif ($ext === 'sol') {
                // Solution file should contain GLPK output
                expect($content)->toContain('Problem:');
                expect($content)->toContain('Status:');
            }
        }
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

        // Should have exactly 2 audit calls: Phase 1 + Phase 2
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Each audit should have its own unique artifacts
        $allArtifactNames = [];
        foreach ($glpkMock->auditCalls as $taskResult) {
            $artifacts = $taskResult->metadata['artifacts'];
            $names = array_keys($artifacts);

            // Each task should have 3 artifacts (mod, dat, sol)
            expect(count($artifacts))->toBe(3);

            // Track all artifact names
            $allArtifactNames = array_merge($allArtifactNames, $names);
        }

        // All artifact filenames should be unique across tasks
        expect(count($allArtifactNames))->toBe(count(array_unique($allArtifactNames)));
    });

    test('metadata includes timing information alongside artifacts', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture();

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have exactly 2 audit calls: Phase 1 + Phase 2
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Verify metadata structure
        foreach ($glpkMock->auditCalls as $taskResult) {
            $metadata = $taskResult->metadata;

            // Should have timing info
            expect($metadata)->toHaveKey('time_ms');
            expect($metadata['time_ms'])->toBeNumeric();
            expect($metadata['time_ms'])->toBeGreaterThan(0);

            // Should have artifacts
            expect($metadata)->toHaveKey('artifacts');
            expect($metadata['artifacts'])->toBeArray();
        }
    });

    test('binary search mode stores Phase 2 artifacts', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture(['glpk_phase1_max_size' => 0, 'timeout' => 10]);

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have 2 audit calls: Phase 1 (binary search) + Phase 2 (GLPK)
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Phase 2 audit should have artifacts (GLPK execution)
        $phase2Result = $glpkMock->auditCalls[1];
        expect($phase2Result->task->value)->toBe('unit_distribution');
        expect($phase2Result->metadata)->toHaveKey('artifacts');

        $artifacts = $phase2Result->metadata['artifacts'];
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
    });

    test('binary search mode Phase 1 has no artifacts but Phase 2 does', function () {
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);
        $glpkMock = mockGlpkWithAuditCapture(['glpk_phase1_max_size' => 0, 'timeout' => 10]);

        $glpkMock->distributeUnits(mockManifest(), $spec);

        // Should have 2 audit calls: Phase 1 (binary search) + Phase 2 (GLPK)
        expect(count($glpkMock->auditCalls))->toBe(2);

        // Phase 1 audit (binary search, no GLPK)
        $phase1Result = $glpkMock->auditCalls[0];
        expect($phase1Result->task->value)->toBe('min_satisfaction');
        expect($phase1Result->metadata)->toHaveKey('artifacts');
        expect($phase1Result->metadata['artifacts'])->toBeArray();
        expect($phase1Result->metadata['artifacts'])->toBeEmpty();

        // Phase 2 audit (GLPK execution)
        $phase2Result = $glpkMock->auditCalls[1];
        expect($phase2Result->task->value)->toBe('unit_distribution');
        expect($phase2Result->metadata)->toHaveKey('artifacts');

        $artifacts = $phase2Result->metadata['artifacts'];
        expect($artifacts)->toBeArray();
        expect($artifacts)->not->toBeEmpty();

        // Verify Phase 2 has all three file types
        $extensions = array_map(
            fn ($filename) => pathinfo($filename, PATHINFO_EXTENSION),
            array_keys($artifacts)
        );
        expect($extensions)->toContain('mod');
        expect($extensions)->toContain('dat');
        expect($extensions)->toContain('sol');
    });
});
