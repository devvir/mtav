<?php

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Task;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\TaskRunnerFactory;

/**
 * GLPK Orchestrator - coordinates task execution and auditing.
 */
class Glpk
{
    protected int $timeout;

    protected int $phase1MaxSize;
    protected float $phase1Timeout;

    public function __construct(
        protected TaskRunnerFactory $taskRunnerFactory,
        protected AuditService $auditService,
    ) {
        $config = config('lottery.solvers.glpk.config');

        $this->timeout = $config['timeout'] ?? 30;

        $this->phase1MaxSize = $config['glpk_phase1_max_size'] ?? 25;
        $this->phase1Timeout = $config['glpk_phase1_timeout'] ?? 0.5;
    }

    /**
     * Distribute units using two-phase optimization.
     * Chooses between GlpkDistribution and HybridDistribution strategies.
     */
    public function distributeUnits(LotteryManifest $manifest, LotterySpec $spec): array
    {
        // Large spec: use Hybrid strategy directly (binary search is faster)
        if ($spec->familyCount() >= $this->phase1MaxSize) {
            return $this->hybridDistribution($manifest, $spec);
        }

        // Small spec: try GLPK phase1 first, fallback to Hybrid on timeout
        try {
            return $this->glpkDistribution($manifest, $spec);
        } catch (GlpkTimeoutException) {
            return $this->hybridDistribution($manifest, $spec);
        }
    }

    /**
     * Identify worst units among candidates.
     */
    public function identifyWorstUnits(LotteryManifest $manifest, LotterySpec $spec): array
    {
        $pruningRunner = $this->taskRunnerFactory->make(Task::WORST_UNITS_PRUNING);
        $taskResult = $pruningRunner->execute($spec, $this->timeout);

        $this->auditTask($manifest, $taskResult, 'success');

        return $taskResult->get('worst_units');
    }

    /**
     * Execute GLPK distribution strategy (GLPK phase1 + GLPK phase2).
     */
    protected function glpkDistribution(LotteryManifest $manifest, LotterySpec $spec): array
    {
        $runner = $this->taskRunnerFactory->make(Task::GLPK_DISTRIBUTION);
        $result = $runner->execute($spec, $this->timeout, [
            'phase1_timeout' => $this->phase1Timeout,
        ]);

        $this->auditTask($manifest, $result, 'success');

        return $result->get('distribution');
    }

    /**
     * Execute Hybrid distribution strategy (binary search + GLPK phase2).
     */
    protected function hybridDistribution(LotteryManifest $manifest, LotterySpec $spec): array
    {
        $runner = $this->taskRunnerFactory->make(Task::HYBRID_DISTRIBUTION);
        $result = $runner->execute($spec, $this->timeout);

        $this->auditTask($manifest, $result, 'success');

        return $result->get('distribution');
    }

    /**
     * Create audit entry for task execution.
     */
    protected function auditTask(LotteryManifest $manifest, TaskResult $taskResult, string $status): void
    {
        $this->auditService->custom($manifest, [
            'task'     => $taskResult->task->value,
            'status'   => $status,
            'result'   => $taskResult->data,
            'metadata' => $taskResult->metadata,
        ]);
    }
}
