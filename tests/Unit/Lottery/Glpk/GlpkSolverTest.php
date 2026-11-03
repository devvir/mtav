<?php

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkException;
use App\Services\Lottery\Solvers\GlpkSolver;

/**
 * The solver on its own, through the real glpsol: the outcomes the first version's suite
 * proved, kept word for word (the lottery's algorithm has not changed since).
 */
uses()->group('Unit.Lottery.Glpk', 'slow');

beforeEach(function () {
    config()->set('logging.default', 'null');
    fakeAudits();
});

describe('GlpkSolver', function () {
    test('balanced optimal assignment respects preferences', function () {
        // Participants with full preference ordering over items
        // Ideal solution: 1->10, 2->20, 3->30 (each gets first choice)
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 30, 10],
            3 => [30, 10, 20],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        expect($result->picks)->toBe([1 => 10, 2 => 20, 3 => 30]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('max-min fairness optimization', function () {
        // Participant 1 strongly prefers item 10 (rank 1), dislikes 20 (rank 2)
        // Participant 2 strongly prefers item 20 (rank 1), dislikes 10 (rank 2)
        // Optimal: Both get rank 1 (worst-case = 1, sum = 2)
        // Suboptimal: One gets rank 1, other gets rank 2 (worst-case = 2, sum = 3)
        $participants = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $items = [10, 20];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Verify both participants get their first choice (max-min fairness)
        expect($result->picks)->toBe([1 => 10, 2 => 20]);
    });

    test('tie breaking with overall satisfaction', function () {
        // Multiple optimal solutions with same worst-case
        // Phase 2 should pick solution with best overall satisfaction
        $participants = [
            1 => [10, 20, 30, 40],
            2 => [20, 10, 30, 40],
            3 => [30, 40, 10, 20],
            4 => [40, 30, 20, 10],
        ];
        $items = [10, 20, 30, 40];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // All participants should be matched
        expect($result->picks)->toHaveCount(4);
        expect($result->orphans['participants'])->toBeEmpty();
        expect($result->orphans['items'])->toBeEmpty();

        // No duplicate assignments
        expect(array_unique(array_values($result->picks)))->toHaveCount(4);

        // Calculate satisfaction score (lower is better)
        $totalSatisfaction = 0;
        foreach ($result->picks as $participantId => $itemId) {
            $rank = array_search($itemId, $participants[$participantId]) + 1;
            $totalSatisfaction += $rank;
        }

        // Should achieve good overall satisfaction (not just max-min)
        expect($totalSatisfaction)->toBeLessThanOrEqual(10);
    });

    test('deterministic across runs for same input', function () {
        $participants = [
            5 => [30, 10, 20, 40],
            2 => [10, 20, 30, 40],
            8 => [40, 30, 20, 10],
            1 => [20, 10, 30, 40],
        ];
        $items = [30, 10, 20, 40];

        $solver = app(GlpkSolver::class);
        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        $result1 = $solver->execute($manifest, $spec);
        $result2 = $solver->execute($manifest, $spec);
        $result3 = $solver->execute($manifest, $spec);

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

    test('larger problem with 10 participants and items', function () {
        // Generate realistic larger problem
        $participants = [];
        $items = range(100, 109);

        // Each participant has different preferences
        foreach (range(1, 10) as $i) {
            $prefs = $items;
            shuffle($prefs);
            $participants[$i] = $prefs;
        }

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should assign all participants to items
        expect($result->picks)->toHaveCount(10);
        expect($result->orphans['participants'])->toBeEmpty();
        expect($result->orphans['items'])->toBeEmpty();

        // No duplicates
        expect(array_unique(array_keys($result->picks)))->toHaveCount(10);
        expect(array_unique(array_values($result->picks)))->toHaveCount(10);

        // All participants assigned
        expect(array_keys($result->picks))->each->toBeIn(range(1, 10));

        // All items assigned
        expect(array_values($result->picks))->each->toBeIn($items);
    });

    test('more items than participants produces orphan items', function () {
        $participants = [
            1 => [10, 20, 30],
            2 => [20, 10, 30],
        ];
        $items = [10, 20, 30];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should assign 2 participants
        expect($result->picks)->toHaveCount(2);
        expect($result->orphans['participants'])->toBeEmpty();
        expect($result->orphans['items'])->toHaveCount(1);

        // Verify orphan item is one of the valid items
        expect($result->orphans['items'][0])->toBeIn($items);

        // No duplicate assignments
        expect(array_unique(array_values($result->picks)))->toHaveCount(2);
    });

    test('more participants than items produces orphan participants', function () {
        $participants = [
            1 => [10, 20],
            2 => [20, 10],
            3 => [10, 20],
        ];
        $items = [10, 20];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        // Should assign 2 participants
        expect($result->picks)->toHaveCount(2);
        expect($result->orphans['participants'])->toHaveCount(1);
        expect($result->orphans['items'])->toBeEmpty();

        // Verify orphan participant is one of the valid participants
        expect($result->orphans['participants'][0])->toBeIn([1, 2, 3]);

        // All items should be assigned
        expect(array_values($result->picks))->each->toBeIn($items);
    });

    test('single participant single item', function () {
        $participants = [100 => [200]];
        $items = [200];

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        $result = app(GlpkSolver::class)->execute($manifest, $spec);

        expect($result->picks)->toBe([100 => 200]);
        expect($result->orphans['participants'])->toBe([]);
        expect($result->orphans['items'])->toBe([]);
    });

    test('throws GlpkException when solver missing', function () {
        $participants = [1 => [10, 20], 2 => [20, 10]];
        $items = [10, 20];

        // Point to a non-existent glpsol path to force failure
        config()->set('lottery.solvers.glpk.config', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => sys_get_temp_dir(),
            'timeout'     => 5,
        ]);

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        // Call solver directly to test GLPK failure, not the whole system
        expect(fn () => app(GlpkSolver::class)->execute($manifest, $spec))->toThrow(GlpkException::class);
    });

    test('reports user-friendly error message on GLPK failure', function () {
        $participants = [1 => [10, 20]];
        $items = [10, 20];

        config()->set('lottery.solvers.glpk.config', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => sys_get_temp_dir(),
        ]);

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        // Call solver directly to test GLPK error message
        try {
            app(GlpkSolver::class)->execute($manifest, $spec);
            $this->fail('Expected GlpkException to be thrown');
        } catch (GlpkException $e) {
            expect($e->getUserMessage())->toBeString();
            expect($e->getUserMessage())->not->toBeEmpty();
        }
    });

    test('temp files are cleaned up after success', function () {
        $participants = [
            1 => [10, 20],
            2 => [20, 10],
        ];
        $items = [10, 20];

        // A folder of its own: tests running alongside leave their files elsewhere
        $tempDir = ownTempDir();

        config()->set('lottery.solvers.glpk.config.temp_dir', $tempDir);
        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);
        app(GlpkSolver::class)->execute($manifest, $spec);

        // Should not leak temp files
        expect(glob($tempDir . '/*'))->toBe([]);
        rmdir($tempDir);
    });

    test('temp files are cleaned up after failure', function () {
        $participants = [1 => [10, 20]];
        $items = [10, 20];

        $tempDir = ownTempDir();

        config()->set('lottery.solvers.glpk.config', [
            'glpsol_path' => '/nonexistent/glpsol',
            'temp_dir'    => $tempDir,
        ]);

        $spec = new LotterySpec($participants, $items);
        $manifest = mockManifest(1, [10 => ['participants' => $participants, 'items' => $items]]);

        try {
            app(GlpkSolver::class)->execute($manifest, $spec);
        } catch (GlpkException $e) {
            // Expected
        }

        // Should not leak temp files even on failure
        expect(glob($tempDir . '/*'))->toBe([]);
        rmdir($tempDir);
    });
});

/** An empty folder only this test writes to */
function ownTempDir(): string
{
    $dir = sys_get_temp_dir() . '/mtav-test-' . bin2hex(random_bytes(6));
    mkdir($dir);

    return $dir;
}
