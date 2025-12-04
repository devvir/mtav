<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\GlpkSolver;
use App\Services\Lottery\Exceptions\GlpkException;

uses()->group('Feature.Lottery.Integration');

describe('GlpkSolver', function () {
    test('balanced optimal assignment respects preferences', function () {
        // Families with full preference ordering over units
        // Ideal solution: 1->10, 2->20, 3->30 (each gets first choice)
        $families = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        expect($result->picks)->toBe([1 => 10, 2 => 20, 3 => 30]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('max-min fairness optimization', function () {
        // Family 1 strongly prefers unit 10 (rank 1), dislikes 20 (rank 2)
        // Family 2 strongly prefers unit 20 (rank 1), dislikes 10 (rank 2)
        // Optimal: Both get rank 1 (worst-case = 1, sum = 2)
        // Suboptimal: One gets rank 1, other gets rank 2 (worst-case = 2, sum = 3)
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        // Verify both families get their first choice (max-min fairness)
        expect($result->picks)->toBe([1 => 10, 2 => 20]);
    });

    test('tie breaking with overall satisfaction', function () {
        // Multiple optimal solutions with same worst-case
        // Phase 2 should pick solution with best overall satisfaction
        $families = [
            1 => [10, 20, 30, 40],
            2 => [20, 10, 30, 40],
            3 => [30, 40, 10, 20],
            4 => [40, 30, 20, 10],
        ];
        $units = [10, 20, 30, 40];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        // All families should be matched
        expect($result->picks)->toHaveCount(4);
        expect($result->orphans['families'])->toBeEmpty();
        expect($result->orphans['units'])->toBeEmpty();

        // No duplicate assignments
        expect(array_unique(array_values($result->picks)))->toHaveCount(4);

        // Calculate satisfaction score (lower is better)
        $totalSatisfaction = 0;
        foreach ($result->picks as $familyId => $unitId) {
            $rank = array_search($unitId, $families[$familyId]) + 1;
            $totalSatisfaction += $rank;
        }

        // Should achieve good overall satisfaction (not just max-min)
        expect($totalSatisfaction)->toBeLessThanOrEqual(10);
    });

    test('deterministic across runs for same input', function () {
        $families = [
            5 => [30, 10, 20, 40],
            2 => [10, 20, 30, 40],
            8 => [40, 30, 20, 10],
            1 => [20, 10, 30, 40],
        ];
        $units = [30, 10, 20, 40];

        $solver = app(GlpkSolver::class);
        $spec = new LotterySpec($families, $units);

        $result1 = $solver->execute($spec);
        $result2 = $solver->execute($spec);
        $result3 = $solver->execute($spec);

        // GLPK optimal solution should be stable for identical input
        expect($result1->picks)->toBe($result2->picks);
        expect($result2->picks)->toBe($result3->picks);
        expect($result1->orphans)->toBe($result2->orphans);
        expect($result2->orphans)->toBe($result3->orphans);

        // Sanity checks: exact matching with no duplicates
        expect(count($result1->picks))->toBe(4);
        expect(array_unique(array_keys($result1->picks)))->toHaveCount(4);
        expect(array_unique(array_values($result1->picks)))->toHaveCount(4);
    });

    test('larger problem with 10 families and units', function () {
        // Generate realistic larger problem
        $families = [];
        $units = range(100, 109);

        // Each family has different preferences
        foreach (range(1, 10) as $i) {
            $prefs = $units;
            shuffle($prefs);
            $families[$i] = $prefs;
        }

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        // Should assign all families to units
        expect($result->picks)->toHaveCount(10);
        expect($result->orphans['families'])->toBeEmpty();
        expect($result->orphans['units'])->toBeEmpty();

        // No duplicates
        expect(array_unique(array_keys($result->picks)))->toHaveCount(10);
        expect(array_unique(array_values($result->picks)))->toHaveCount(10);

        // All families assigned
        expect(array_keys($result->picks))->each->toBeIn(range(1, 10));

        // All units assigned
        expect(array_values($result->picks))->each->toBeIn($units);
    });

    test('more units than families produces orphan units', function () {
        $families = [
            1 => [10, 20, 30],
            2 => [20, 10, 30],
        ];
        $units = [10, 20, 30];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        // Should assign 2 families
        expect($result->picks)->toHaveCount(2);
        expect($result->orphans['families'])->toBeEmpty();
        expect($result->orphans['units'])->toHaveCount(1);

        // Verify orphan unit is one of the valid units
        expect($result->orphans['units'][0])->toBeIn($units);

        // No duplicate assignments
        expect(array_unique(array_values($result->picks)))->toHaveCount(2);
    });

    test('more families than units produces orphan families', function () {
        $families = [
            1 => [10, 20],
            2 => [20, 10],
            3 => [10, 20],
        ];
        $units = [10, 20];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        // Should assign 2 families
        expect($result->picks)->toHaveCount(2);
        expect($result->orphans['families'])->toHaveCount(1);
        expect($result->orphans['units'])->toBeEmpty();

        // Verify orphan family is one of the valid families
        expect($result->orphans['families'][0])->toBeIn([1, 2, 3]);

        // All units should be assigned
        expect(array_values($result->picks))->each->toBeIn($units);
    });

    test('single family single unit', function () {
        $families = [100 => [200]];
        $units = [200];

        $spec = new LotterySpec($families, $units);
        $result = app(GlpkSolver::class)->execute($spec);

        expect($result->picks)->toBe([100 => 200]);
        expect($result->orphans['families'])->toBe([]);
        expect($result->orphans['units'])->toBe([]);
    });

    test('throws GlpkException when solver missing', function () {
        $families = [1 => [10, 20], 2 => [20, 10]];
        $units = [10, 20];

        // Point to a non-existent glpsol path to force failure
        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => sys_get_temp_dir(),
            'timeout'     => 5,
        ]);

        $spec = new LotterySpec($families, $units);

        expect(fn () => app(GlpkSolver::class)->execute($spec))->toThrow(GlpkException::class);
    });

    test('throws GlpkException with user-friendly message', function () {
        $families = [1 => [10, 20]];
        $units = [10, 20];

        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => sys_get_temp_dir(),
        ]);

        $spec = new LotterySpec($families, $units);

        try {
            app(GlpkSolver::class)->execute($spec);
            $this->fail('Expected GlpkException to be thrown');
        } catch (GlpkException $e) {
            expect($e->getUserMessage())->toBeString();
            expect($e->getUserMessage())->not->toBeEmpty();
        }
    });

    test('temp files are cleaned up after success', function () {
        $families = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $units = [10, 20];

        $tempDir = sys_get_temp_dir();
        $beforeFiles = glob($tempDir . '/mtav_*');

        config()->set('lottery.solvers.glpk.temp_dir', $tempDir);
        $spec = new LotterySpec($families, $units);
        app(GlpkSolver::class)->execute($spec);

        $afterFiles = glob($tempDir . '/mtav_*');

        // Should not leak temp files
        expect(count($afterFiles))->toBe(count($beforeFiles));
    });

    test('temp files are cleaned up after failure', function () {
        $families = [1 => [10, 20]];
        $units = [10, 20];

        $tempDir = sys_get_temp_dir();
        $beforeFiles = glob($tempDir . '/mtav_*');

        config()->set('lottery.solvers.glpk', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => $tempDir,
        ]);

        $spec = new LotterySpec($families, $units);

        try {
            app(GlpkSolver::class)->execute($spec);
        } catch (GlpkException $e) {
            // Expected
        }

        $afterFiles = glob($tempDir . '/mtav_*');

        // Should not leak temp files even on failure
        expect(count($afterFiles))->toBe(count($beforeFiles));
    });
});
