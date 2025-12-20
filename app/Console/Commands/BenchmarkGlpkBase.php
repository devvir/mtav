<?php

// Copilot - Pending review

namespace App\Console\Commands;

use App\Models\LotteryAudit;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;
use App\Services\Lottery\Solvers\GlpkSolver;
use Exception;
use Illuminate\Console\Command;
use Mockery;

abstract class BenchmarkGlpkBase extends Command
{
    protected $csv;
    protected GlpkSolver $solver;
    protected LotteryManifest $manifest;
    protected array $results = [];

    protected function configureSolver(int $timeout): void
    {
        config()->set('lottery.solvers.default', 'glpk');
        config()->set('lottery.solvers.glpk.config.timeout', $timeout);
    }

    protected function initializeSolver(): void
    {
        // Mock AuditService to avoid database operations during benchmarking
        $mockAudit = Mockery::mock(AuditService::class);
        $mockAuditRecord = Mockery::mock(LotteryAudit::class);
        $mockAudit->shouldReceive('custom')->andReturn($mockAuditRecord);
        app()->instance(AuditService::class, $mockAudit);

        $this->solver = app(GlpkSolver::class);

        $this->manifest = new class () extends LotteryManifest {
            public function __construct()
            {
            }
        };
    }

    protected function createOutputFile(string $outputPath, bool $writeHeader = true): void
    {
        $directory = dirname($outputPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $fileExists = file_exists($outputPath);
        $this->csv = fopen($outputPath, 'a');

        if ($this->csv === false) {
            $this->error("Failed to create output file: {$outputPath}");
            exit(1);
        }

        if (!$fileExists && $writeHeader) {
            fputcsv($this->csv, ['size', 'scenario', 'iteration', 'time_ms', 'status', 'error', 'spec', 'result']);
        }
    }

    protected function executeSolver(LotterySpec $spec): array
    {
        $startTime = microtime(true);
        $status = 'SUCCESS';
        $error = '';
        $result = null;

        try {
            $executionResult = $this->solver->execute($this->manifest, $spec);
            $result = $executionResult->picks;
        } catch (GlpkInfeasibleException $e) {
            $error = $e->getMessage();
            $status = 'INFEASIBLE';
        } catch (GlpkTimeoutException $e) {
            $error = $e->getMessage();
            $status = str_contains(strtolower($error), 'timeout') ? 'TIMEOUT' : 'FAILED';
        } catch (Exception $e) {
            $error = $e->getMessage();
            $status = 'FAILED';
        }

        $timeMs = (microtime(true) - $startTime) * 1000;

        return [
            'time_ms' => $timeMs,
            'status'  => $status,
            'error'   => $error,
            'result'  => $result,
        ];
    }

    protected function recordResult(int $size, string $scenario, int $iteration, array $result, LotterySpec $spec): void
    {
        $specJson = $this->generateSpecJson($spec);
        $resultJson = $result['result'] ? json_encode($result['result'], JSON_UNESCAPED_SLASHES) : '';

        $row = [
            $size,
            $scenario,
            $iteration,
            $result['time_ms'],
            $result['status'],
            substr($result['error'], 0, 100),
            $specJson,
            $resultJson,
        ];

        fputcsv($this->csv, $row);
        fflush($this->csv);

        $this->results[] = $result;
        $this->outputResultToTerminal(array_slice($row, 0, 6)); // Don't show spec/result in terminal
    }

    protected function generateSpecJson(LotterySpec $spec): string
    {
        $data = [
            'units'       => $spec->units,
            'preferences' => $spec->families,
        ];

        return json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    protected function outputResultToTerminal(array $row): void
    {
        [$size, $scenario, $iteration, $timeMs, $status, $error] = $row;

        $statusColor = match ($status) {
            'SUCCESS'    => 'fg=green',
            'TIMEOUT'    => 'fg=yellow',
            'INFEASIBLE' => 'fg=magenta',
            'FAILED'     => 'fg=red',
            default      => 'fg=gray',
        };

        $this->line(sprintf(
            "| <fg=cyan>%3d</> | <fg=gray>%-4s</> | <fg=gray>%-8s</> | <fg=yellow>%9.2f</> | <%s>%-11s</> |%s",
            $iteration,
            $size,
            $scenario,
            $timeMs,
            $statusColor,
            $status,
            $error ? ' <fg=red>' . substr($error, 0, 120) . '</>' : ''
        ));
    }

    protected function displayTableHeader(): void
    {
        $this->table(
            ['   ', 'Size', 'Scenario', 'Time (ms)', 'Status      ', 'Error'],
            []
        );
    }

    protected function displaySummary(): void
    {
        if (empty($this->results)) {
            return;
        }

        $total = count($this->results);
        $successCount = count(array_filter($this->results, fn ($r) => $r['status'] === 'SUCCESS'));
        $times = array_column($this->results, 'time_ms');
        $avgTime = array_sum($times) / count($times);

        $this->newLine();
        $this->line(sprintf(
            "| <fg=bright-cyan>%3s</> | <fg=gray>%-4s</> | <fg=gray>%-8s</> | <fg=bright-yellow>%9.2f</> | <fg=bright-green>%-6s</> |",
            '',
            '',
            '',
            $avgTime,
            "{$successCount}/{$total}"
        ));
    }

    protected function cleanup(): void
    {
        if ($this->csv) {
            fclose($this->csv);
        }

        Mockery::close();
    }
}
