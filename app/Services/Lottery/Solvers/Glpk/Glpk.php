<?php

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Solvers\Glpk\DataObjects\TaskResult;
use App\Services\Lottery\Solvers\Glpk\Enums\FeasibilityResult;
use App\Services\Lottery\Solvers\Glpk\Enums\Tasks;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkInfeasibleException;
use App\Services\Lottery\Solvers\Glpk\Exceptions\GlpkTimeoutException;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\MinSatisfaction;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\TaskRunnerFactory;
use App\Services\Lottery\Solvers\Glpk\TaskRunners\UnitDistribution;
use Exception;
use Generator;

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
        $this->timeout = config('lottery.solvers.glpk.config.timeout', 30);
        $this->phase1MaxSize = config('lottery.solvers.glpk.config.glpk_phase1_max_size', 25);
        $this->phase1Timeout = config('lottery.solvers.glpk.config.glpk_phase1_timeout', 0.5);
    }

    /**
     * Distribute units using two-phase GLPK optimization.
     */
    public function distributeUnits(LotteryManifest $manifest, LotterySpec $spec): array
    {
        if ($spec->familyCount() >= $this->phase1MaxSize) {
            /** Glpk Phase1 is always slower for large specs */
            return $this->binarySearchAssistedDistribution($manifest, $spec);
        }

        try {
            return $this->directGlpkDistribution($manifest, $spec);
        } catch (GlpkTimeoutException) {
            return $this->binarySearchAssistedDistribution($manifest, $spec);
        }
    }

    /**
     * Direct two-phase GLPK optimization (Phase 1 → Phase 2).
     */
    protected function directGlpkDistribution(LotteryManifest $manifest, LotterySpec $spec): array
    {
        /** Phase 1: find minimum satisfaction constraint */
        $glpkPhase1Timeout = min($this->timeout, $this->phase1Timeout);
        $minSatisfactionRunner = $this->taskRunnerFactory->make(Tasks::MIN_SATISFACTION);
        $phase1Result = $minSatisfactionRunner->execute($spec, $glpkPhase1Timeout);
        $this->auditTask($manifest, $phase1Result, 'success');

        /** Phase 2: distribute units based on minimum satisfaction */
        $distributionRunner = $this->taskRunnerFactory->make(Tasks::UNIT_DISTRIBUTION);
        $phase2Result = $distributionRunner->execute($spec, $this->timeout, [
            'min_satisfaction' => $phase1Result->get('min_satisfaction'),
        ]);
        $this->auditTask($manifest, $phase2Result, 'success');

        return $phase2Result->get('distribution');
    }

    /**
     * Binary search fallback when Phase 1 times out.
     * Uses Phase 2 feasibility checks to guide the search.
     */
    protected function binarySearchAssistedDistribution(LotteryManifest $manifest, LotterySpec $spec): array
    {
        /** @var MinSatisfaction $minSatisfactionRunner */
        $minSatisfactionRunner = $this->taskRunnerFactory->make(Tasks::MIN_SATISFACTION);
        $generator = $minSatisfactionRunner->binarySearchGenerator($spec);

        $stepTimeout = $this->binarySearchStepTimeout($spec);
        $phase2Result = $this->searchBestDistribution($generator, $spec, $stepTimeout);

        if (empty($phase2Result)) {
            throw new Exception('Binary search completed without finding any feasible solution');
        }

        $phase1Result = $generator->getReturn();

        $this->auditTask($manifest, $phase1Result, 'success');
        $this->auditTask($manifest, $phase2Result, 'success');

        return $phase2Result->get('distribution');
    }

    /**
     * Search for best distribution using binary search generator.
     */
    protected function searchBestDistribution(Generator $generator, LotterySpec $spec, float $timeout): ?TaskResult
    {
        /** @var UnitDistribution $distributionRunner */
        $distributionRunner = $this->taskRunnerFactory->make(Tasks::UNIT_DISTRIBUTION);

        while ($generator->valid()) {
            try {
                $phase2Result = $distributionRunner->execute($spec, $timeout, [
                    'min_satisfaction' => $generator->current(),
                ]);
                $generator->send(FeasibilityResult::FEASIBLE);
            } catch (GlpkInfeasibleException) {
                $generator->send(FeasibilityResult::INFEASIBLE);
            }
        }

        return $phase2Result ?? null;
    }

    /**
     * Set timeout for each binary search step, for a total timeout no larger than twice
     * the timeout for the whole search (hard upper bound, impossible, allows execution for
     * up to 2*$this->timeout, where each step takes exactly the allowed time without timeout).
     */
    protected function binarySearchStepTimeout(LotterySpec $spec): float
    {
        $steps = (int) ceil(log($spec->familyCount(), 2)) + 1;

        return 2 * $this->timeout / $steps;
    }

    /**
     * Identify worst units among candidates.
     */
    public function identifyWorstUnits(LotteryManifest $manifest, LotterySpec $spec): array
    {
        $pruningRunner = $this->taskRunnerFactory->make(Tasks::WORST_UNITS_PRUNING);
        $taskResult = $pruningRunner->execute($spec, $this->timeout);

        $this->auditTask($manifest, $taskResult, 'success');

        return $taskResult->get('worst_units');
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
