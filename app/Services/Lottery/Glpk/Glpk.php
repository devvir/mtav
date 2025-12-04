<?php

namespace App\Services\Lottery\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Exceptions\GlpkException;
use Illuminate\Support\Facades\Log;

/**
 * Low-level GLPK execution wrapper.
 */
class Glpk
{
    protected string $glpsolPath;
    protected string $tempDir;
    protected int $timeout;

    public function __construct(
        protected ModelGenerator $modelGenerator,
        protected DataGenerator $dataGenerator,
        protected SolutionParser $solutionParser,
    ) {
        $config = config('lottery.solvers.glpk');

        $this->glpsolPath = $config['glpsol_path'] ?? '/usr/bin/glpsol';
        $this->tempDir = $config['temp_dir'] ?? sys_get_temp_dir();
        $this->timeout = $config['timeout'] ?? 30;

        Log::info('Glpk instantiated.', compact('config'));
    }

    /**
     * Execute two-phase GLPK optimization to distribute units to families.
     *
     * Phase 1: Find minimum satisfaction level (max-min fairness).
     * Phase 2: Maximize overall satisfaction given minimum constraint.
     *
     * @return array<int, int> Array of family_id => unit_id assignments
     */
    public function distributeUnits(LotterySpec $spec): array
    {
        // Phase 1: Find minimum satisfaction level
        Log::info('Glpk - Phase 1.');
        $minSatisfaction = $this->executePhase1($spec);

        // Phase 2: Maximize overall satisfaction given minSatisfaction
        Log::info('Glpk - Phase 2.', compact('minSatisfaction'));
        return $this->executePhase2($spec, $minSatisfaction);
    }

    /**
     * Execute Phase 1: Maximize minimum satisfaction.
     *
     * @return int Minimum satisfaction level (worst-case preference rank)
     */
    protected function executePhase1(LotterySpec $spec): int
    {
        $modFile = $this->writeFile('phase1_', '.mod', $this->modelGenerator->generatePhase1Model());
        $datFile = $this->writeFile('data_', '.dat', $this->dataGenerator->generateData($spec));

        try {
            $solFile = $this->runGlpk($modFile, $datFile);
            $minSatisfaction = $this->solutionParser->extractObjective($solFile);
        } finally {
            $this->cleanup([$modFile, $datFile, $solFile ?? null]);
        }

        return $minSatisfaction;
    }

    /**
     * Execute Phase 2: Maximize overall satisfaction given minimum satisfaction constraint.
     *
     * @param  int  $minSatisfaction  The S value from Phase 1
     * @return array<int, int> Array of family_id => unit_id assignments
     */
    protected function executePhase2(LotterySpec $spec, int $minSatisfaction): array
    {
        $modFile = $this->writeFile('phase2_', '.mod', $this->modelGenerator->generatePhase2Model());
        $datFile = $this->writeFile('data_s_', '.dat', $this->dataGenerator->generateDataWithS($spec, $minSatisfaction));

        try {
            $solFile = $this->runGlpk($modFile, $datFile);
            $picks = $this->solutionParser->extractAssignments($solFile);
        } finally {
            $this->cleanup([$modFile, $datFile, $solFile ?? null]);
        }

        return $picks;
    }

    /**
     * Identify worst units among candidates using GLPK optimization.
     *
     * Runs optimization to select exactly M units from candidates while minimizing worst-case satisfaction.
     * Units not selected are the worst.
     *
     * @return array<int> Array of unit IDs to discard
     */
    public function identifyWorstUnits(LotterySpec $spec): array
    {
        try {
            $modFile = $this->writeFile(
                'unit_selection_',
                '.mod',
                $this->modelGenerator->generateUnitSelectionModel()
            );

            $datFile = $this->writeFile(
                'data_units_',
                '.dat',
                $this->dataGenerator->generateDataWithUnitCount($spec, count($spec->families))
            );

            $solFile = $this->runGlpk($modFile, $datFile);
            $unusedUnits = $this->solutionParser->extractUnusedUnits($solFile);
        } finally {
            $this->cleanup([$modFile, $datFile, $solFile ?? null]);
        }

        return $unusedUnits;
    }

    /**
     * Run GLPK solver on model and data files.
     *
     * @param  string  $modFile  Path to .mod file
     * @param  string  $datFile  Path to .dat file
     * @return string Path to generated .sol file
     *
     * @throws GlpkException if GLPK execution fails
     */
    public function runGlpk(string $modFile, string $datFile): string
    {
        // Create temp file for solution, fixing tempnam() leak
        $tempFile = tempnam($this->tempDir, 'mtav_sol_');

        if ($tempFile === false) {
            throw new GlpkException("Failed to create temporary solution file in {$this->tempDir}");
        }

        unlink($tempFile);
        $solFile = $tempFile . '.sol';

        // Use GLPK's built-in --tmlim parameter (time limit in milliseconds)
        $tmlimMs = (int) ($this->timeout * 1000);

        $command = sprintf(
            '%s --model %s --data %s --tmlim %d --output %s 2>&1',
            escapeshellarg($this->glpsolPath),
            escapeshellarg($modFile),
            escapeshellarg($datFile),
            $tmlimMs,
            escapeshellarg($solFile)
        );

        exec($command, $output, $returnCode);

        $outputStr = implode("\n", $output);

        // GLPK returns 0 even on timeout, so check output for timeout messages
        if (str_contains($outputStr, 'TIME LIMIT EXCEEDED')) {
            throw new GlpkException("GLPK execution timed out after {$this->timeout} seconds.");
        }        // Any other non-zero return code means failure
        if ($returnCode !== 0) {
            throw new GlpkException(
                "GLPK execution failed with return code {$returnCode}.\nCommand: {$command}\nOutput: " . $outputStr
            );
        }

        // Only use solution file if GLPK completed successfully (return code 0)
        if (! file_exists($solFile) || filesize($solFile) === 0) {
            throw new GlpkException('GLPK did not generate a solution file.');
        }

        return $solFile;
    }

    /**
     * Write content to temporary file.
     *
     * @param  string  $prefix  Filename prefix
     * @param  string  $suffix  Filename suffix (extension)
     * @param  string  $content  File content
     * @return string Path to created file
     *
     * @throws GlpkException if file cannot be created or written
     */
    public function writeFile(string $prefix, string $suffix, string $content): string
    {
        try {
            // Create temp file with prefix
            $tempFile = tempnam($this->tempDir, $prefix);

            if ($tempFile === false) {
                throw new GlpkException("Failed to create temporary file in {$this->tempDir}");
            }

            // Delete the tempnam file and create new one with suffix
            unlink($tempFile);
            $file = $tempFile . $suffix;

            $result = file_put_contents($file, $content);

            if ($result === false) {
                throw new GlpkException("Failed to write content to file {$file}");
            }

            return $file;
        } catch (GlpkException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new GlpkException("Error creating temporary file: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Clean up temporary files.
     *
     * @param  array<string>  $files  Array of file paths to delete
     */
    public function cleanup(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
}
