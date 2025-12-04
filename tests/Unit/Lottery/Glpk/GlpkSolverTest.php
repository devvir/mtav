<?php

// Copilot - Pending review

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\GlpkSolver;

uses()->group('Unit.Lottery.Glpk');

describe('GlpkSolver GLPK-specific failure scenarios', function () {
    test('throws exception when glpsol binary not found', function () {
        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => sys_get_temp_dir(),
            'timeout'     => 5,
        ]);

        $spec = new LotterySpec(
            families: [1 => [10, 20], 2 => [20, 10]],
            units: [10, 20]
        );

        expect(fn () => app(GlpkSolver::class)->execute($spec))->toThrow(GlpkException::class);
    });

    test('throws exception when temp directory is not writable', function () {
        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => 'glpsol',
            'temp_dir'    => '/root/unwritable', // Assuming tests don't run as root
            'timeout'     => 5,
        ]);

        $spec = new LotterySpec(
            families: [1 => [10, 20], 2 => [20, 10]],
            units: [10, 20]
        );

        expect(fn () => app(GlpkSolver::class)->execute($spec))->toThrow(GlpkException::class);
    });

    test('cleans up temp files even when execution fails', function () {
        $tempDir = sys_get_temp_dir();
        $beforeFiles = glob($tempDir . '/mtav_*');

        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => $tempDir,
            'timeout'     => 5,
        ]);

        $spec = new LotterySpec(
            families: [1 => [10, 20], 2 => [20, 10]],
            units: [10, 20]
        );

        try {
            app(GlpkSolver::class)->execute($spec);
        } catch (GlpkException $e) {
            // Expected failure
        }

        $afterFiles = glob($tempDir . '/mtav_*');

        // Should not leak files even on failure
        expect(count($afterFiles))->toBe(count($beforeFiles));
    });

    test('throws exception when execution times out', function () {
        // Set GLPK's time limit to 1ms - impossibly short
        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => 'glpsol',
            'temp_dir'    => sys_get_temp_dir(),
            'timeout'     => 0.001, // 1 millisecond (converted to --tmlim 1)
        ]);

        // Create a large problem that requires significant solving time
        $families = [];
        $units = range(100, 149); // 50 units

        foreach (range(1, 50) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);

        expect(fn () => app(GlpkSolver::class)->execute($spec))
            ->toThrow(GlpkException::class, 'timed out');
    });
});
