<?php

namespace App\Console\Commands;

use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Glpk;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\TaskRunnerFactory;

/**
 * Benchmark GLPK solver performance across problem sizes and preference scenarios.
 *
 * This command generates synthetic lottery scenarios and measures GLPK solver performance,
 * recording execution time, success/failure status, and error details to CSV files.
 *
 * Usage:
 *   php artisan benchmark:glpk --size=5 --scenario=random --iterations=1000
 *   php artisan benchmark:glpk --size=20 --scenario=realistic --iterations=100 --timeout=60
 *   php artisan benchmark:glpk --size=30 --scenario=random --iterations=10000 --original
 *     (Phase 1 with GLPK, the original method, written to storage/benchmarks/original/)
 *
 * Scenarios:
 *   - identical: All families have identical preferences (worst-case for tie-breaking)
 *   - random: Completely random preference orderings
 *   - opposite: Families have opposing preferences (best-case for assignments)
 *   - realistic: Units popular with (almost) everyone and units nobody wants, shared by all
 *     families, each ranking with its own variation: what collides in a real project
 *
 * Output CSV format:
 *   size,scenario,iteration,time_ms,status,error,spec,result  (spec and result as JSON)
 *
 * Status values:
 *   - SUCCESS: Solver completed successfully
 *   - TIMEOUT: Exceeded configured time limit
 *   - INFEASIBLE: GLPK determined no valid assignment exists
 *   - FAILED: Unexpected error occurred
 *
 * Workflow:
 *   1. Run this command to generate initial benchmark data
 *   2. Use benchmark:glpk:retry to re-test any failures with different iterations/timeout
 *   3. Analyze CSV results to identify patterns in failures or performance issues
 *
 * @see BenchmarkGlpkSolverRetry For retrying specific failures from previous runs
 * @see scripts/benchmark_analysis/README.md Analysing what it writes
 */
class BenchmarkGlpkSolver extends BenchmarkGlpkBase
{
    protected $signature = 'benchmark:glpk
                            {output? : Output CSV file path}
                            {--size= : Problem size (NxN)}
                            {--scenario= : Scenario type (identical|random|opposite|realistic)}
                            {--iterations=1 : Number of iterations to run}
                            {--timeout=30 : Solver timeout in seconds}
                            {--original : Solve Phase 1 with GLPK directly, the original method, with no fallback to the binary search}';

    protected $description = 'Benchmark GLPK solver performance across different problem sizes and scenarios';

    public function handle(): int
    {
        $size = $this->getRequiredOption('size');
        $scenario = $this->getRequiredOption('scenario');
        $iterations = (int) $this->option('iterations');
        $timeout = (int) $this->option('timeout');

        $this->validateInputs($size, $scenario, $iterations, $timeout);

        $outputPath = $this->getOutputPath($size, $scenario);

        $this->configureSolver($timeout);

        if ($this->option('original')) {
            $this->useOriginalPhase1();
        }

        $this->initializeSolver();
        $this->createOutputFile($outputPath);

        $method = $this->option('original') ? 'original Phase 1 with GLPK' : 'binary search';
        $this->info("GLPK Benchmark: {$size}x{$size} {$scenario} x{$iterations} (timeout: {$timeout}s, {$method})");
        $this->info("Output file: {$outputPath}");
        $this->newLine();

        $this->runBenchmark($size, $scenario, $iterations);

        $this->cleanup();

        $this->newLine();
        $this->info("Results saved to: {$outputPath}");

        return self::SUCCESS;
    }

    private function getOutputPath(string $size, string $scenario): string
    {
        $output = $this->argument('output');

        if ($output) {
            return $output;
        }

        $folder = $this->option('original') ? 'benchmarks/original' : 'benchmarks';

        return storage_path("{$folder}/glpk_{$scenario}_{$size}.csv");
    }

    /**
     * The original method, as the thesis compares against it: Phase 1 solved by GLPK with
     * the whole time budget and no fallback, so that a timeout is recorded as such.
     */
    private function useOriginalPhase1(): void
    {
        app()->bind(Glpk::class, fn ($app) => new class ($app->make(TaskRunnerFactory::class), $app->make(AuditService::class)) extends Glpk {
            public function distributeItems(LotteryManifest $manifest, LotterySpec $spec): array
            {
                $this->phase1Timeout = $this->timeout;

                return $this->glpkDistribution($manifest, $spec);
            }
        });
    }

    private function getRequiredOption(string $name): string
    {
        $value = $this->option($name);

        if (empty($value)) {
            $this->error("The --{$name} option is required");
            exit(1);
        }

        return $value;
    }

    private function validateInputs(string $size, string $scenario, int $iterations, int $timeout): void
    {
        if (! ctype_digit($size) || (int) $size <= 0) {
            $this->error('Size must be a positive integer');
            exit(1);
        }

        $validScenarios = ['identical', 'random', 'opposite', 'realistic'];

        if (! in_array($scenario, $validScenarios)) {
            $this->error('Scenario must be one of: ' . implode(', ', $validScenarios));
            exit(1);
        }

        if ($iterations <= 0) {
            $this->error('Iterations must be a positive integer');
            exit(1);
        }

        if ($timeout <= 0) {
            $this->error('Timeout must be a positive integer');
            exit(1);
        }
    }

    private function runBenchmark(string $size, string $scenario, int $iterations): void
    {
        $sizeInt = (int) $size;

        $this->displayTableHeader();

        for ($i = 1; $i <= $iterations; $i++) {
            $this->runSingleIteration($sizeInt, $scenario, $i);
        }

        $this->displaySummary();
    }

    private function runSingleIteration(int $size, string $scenario, int $iteration): void
    {
        $spec = $this->generateSpec($size, $scenario);
        $result = $this->executeSolver($spec);

        $this->recordResult($size, $scenario, $iteration, $result, $spec);
    }

    private function generateSpec(int $size, string $scenario): LotterySpec
    {
        $familyIds = range(1, $size);
        $unitIds = range(1001, 1000 + $size);

        $preferences = $this->generatePreferences($size, $scenario, $familyIds, $unitIds);

        return new LotterySpec(
            array_combine($familyIds, $preferences),
            $unitIds
        );
    }

    private function generatePreferences(int $size, string $scenario, array $familyIds, array $unitIds): array
    {
        return match ($scenario) {
            'identical' => array_fill(0, $size, $unitIds),
            'random'    => array_map(fn () => $this->shuffleCopy($unitIds), $familyIds),
            'opposite'  => array_map(
                fn ($i) => $i < $size / 2 ? $unitIds : array_reverse($unitIds),
                array_keys($familyIds)
            ),
            'realistic' => $this->generateRealisticPreferences($unitIds, count($familyIds)),
        };
    }

    /**
     * What a real project looks like: some units are attractive to nearly everyone (the
     * garden, the corner away from the street), some to nearly no one, and each family
     * still has its own order within that. The popular and unpopular sets are drawn ONCE
     * per instance and shared by every family, so many families collide on the same top
     * choices: the kind of instance that made the original Phase 1 take minutes.
     *
     * @return list<list<int>> one ranking per family
     */
    private function generateRealisticPreferences(array $unitIds, int $families): array
    {
        $size = count($unitIds);

        // Determine popular (top 20%) and unpopular (bottom 20%) units, the same for all families
        $popularCount = (int) ceil($size * 0.2);
        $unpopularCount = (int) ceil($size * 0.2);

        $shuffled = $this->shuffleCopy($unitIds);
        $popular = array_slice($shuffled, 0, $popularCount);
        $neutral = array_slice($shuffled, $popularCount, $size - $popularCount - $unpopularCount);
        $unpopular = array_slice($shuffled, -$unpopularCount);

        return array_map(
            fn () => $this->rankWithBias($unitIds, $popular, $neutral, $unpopular),
            range(1, $families),
        );
    }

    /** One family's ranking: popular units tend to the top, unpopular ones to the bottom */
    private function rankWithBias(array $unitIds, array $popular, array $neutral, array $unpopular): array
    {
        $size = count($unitIds);

        // Build weighted preference list
        // Popular units appear 3x as often in early positions
        // Unpopular units appear 3x as often in late positions
        $preferences = [];

        // First third: heavily favor popular units
        $pool = array_merge($popular, $popular, $popular, $neutral);
        shuffle($pool);
        $preferences = array_merge($preferences, array_slice($pool, 0, (int) ceil($size / 3)));

        // Middle third: neutral distribution
        $pool = array_merge($popular, $neutral, $neutral, $unpopular);
        shuffle($pool);
        $preferences = array_merge($preferences, array_slice($pool, 0, (int) ceil($size / 3)));

        // Last third: heavily favor unpopular units
        $pool = array_merge($neutral, $unpopular, $unpopular, $unpopular);
        shuffle($pool);
        $preferences = array_merge($preferences, $pool);

        // Ensure we have exactly $size unique preferences
        $preferences = array_unique($preferences);
        $missing = array_diff($unitIds, $preferences);
        $preferences = array_merge($preferences, $missing);

        return array_slice($preferences, 0, $size);
    }

    private function shuffleCopy(array $array): array
    {
        $copy = $array;
        shuffle($copy);

        return $copy;
    }
}
