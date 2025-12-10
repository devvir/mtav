<?php

namespace App\Services\Lottery;

use App\Events\Lottery\GroupLotteryExecuted;
use App\Events\Lottery\ProjectLotteryExecuted;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Exceptions\GlpkException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Orchestrates the multi-phase lottery execution process.
 *
 * This service operates at a lower abstraction level than ExecutionService, dealing with
 * raw data structures (lists of numbers -- IDs), not Eloquent models.
 */
class LotteryOrchestrator
{
    public function __construct(
        protected SolverInterface $solver,
        protected LotteryManifest $manifest,
        protected AuditService $auditService,
        protected ExecutionService $executionService,
    ) {
        // ...
    }

    /**
     * @param SolverInterface $solver The strategy for executing individual lotteries
     * @param LotteryManifest $manifest Complete lottery manifest with all unit types
     */
    public static function make(SolverInterface $solver, LotteryManifest $manifest): self
    {
        return new self(
            $solver,
            $manifest,
            app(AuditService::class),
            app(ExecutionService::class)
        );
    }

    /**
     * Orchestrate the complete lottery execution across all phases.
     */
    public function execute(): ExecutionResult
    {
        Log::info('LotteryOrchestrator executing Lottery.');

        try {
            return $this->executeProjectLottery();
        } catch (GlpkException $e) {
            return $this->handleExecutionFailure($e, 'glpk_error');
        } catch (LotteryExecutionException $e) {
            return $this->handleExecutionFailure($e, 'execution_error');
        } catch (Throwable $e) {
            return $this->handleExecutionFailure($e, 'system_error');
        }
    }

    /**
     * Execute the complete Project lottery in group-based stages.
     *
     * @throws GlpkException if GlpkExcutor (production) fails
     * @throws LotteryExecutionException on general, non-Glpk failures
     */
    protected function executeProjectLottery(): ExecutionResult
    {
        $groups = $this->manifest->data;

        // Execute Lottery for each group, then for remnants (if any)
        $firstPassResults = collect($groups)->map($this->executeLottery(...));
        $secondChanceResults = $this->distributeOrphans($firstPassResults);

        // Consolidate results for the whole Project
        $results = $firstPassResults->add($secondChanceResults);

        $globalResult = new ExecutionResult(
            $results->mapWithKeys(fn ($result) => $result->picks)->all(),
            $results->flatMap(fn ($result) => $result->orphans)->all()
        );

        return tap($globalResult, fn () => $this->reportGlobalResult($globalResult));
    }

    /**
     * Execute lottery for a given group of families (with picks) and units.
     *
     * @throws GlpkException
     * @throws LotteryExecutionException
     */
    protected function executeLottery(array $data): ExecutionResult
    {
        $spec = new LotterySpec($data['families'], $data['units']);

        $result = $this->solver->execute($this->manifest, $spec);

        GroupLotteryExecuted::dispatch($this->manifest, $this->solver, $result);

        return $result;
    }

    /**
     * Distribute remnant families and units, from previous unbalanced groups' executions.
     */
    protected function distributeOrphans(Collection $firstPassResults): ExecutionResult
    {
        $orphans = [
            'units'    => $firstPassResults->flatMap(fn ($result) => $result->orphans['units'])->all(),
            'families' => $firstPassResults->flatMap(fn ($result) => $result->orphans['families'])
                ->mapWithKeys(fn ($familyId) => [$familyId => []])->all(),
        ];

        if (! $orphans['units'] || ! $orphans['families']) {
            return new ExecutionResult([], [
                'families' => array_keys($orphans['families']),
                'units'    => $orphans['units'],
            ]);
        }

        return $this->executeLottery($orphans);
    }

    /**
     * Report execution results.
     */
    protected function reportGlobalResult(ExecutionResult $result): void
    {
        ProjectLotteryExecuted::dispatch($this->manifest, $this->solver, $result);

        Log::info('Lottery execution completed', [
            'project_id'      => $this->manifest->projectId,
            'total_picks'     => count($result->picks),
            'orphan_families' => count($result->orphans['families']),
            'orphan_units'    => count($result->orphans['units']),
        ]);
    }

    /**
     * Handle execution failure: audit, log, invalidate, and report.
     */
    protected function handleExecutionFailure(Throwable $exception, string $errorType): ExecutionResult
    {
        $this->reportException($exception, $errorType);

        // Lottery Audit
        $this->auditService->exception($this->manifest, $errorType, $exception);

        // Cancel/revert Lottery execution, allowing manual retries
        $this->executionService->cancelExecutionReservation($this->manifest->lotteryId);

        return new ExecutionResult([], ['families' => [], 'units' => []]);
    }

    /**
     * Generate summary log and full report for any catchable exception thrown.
     */
    protected function reportException(Throwable $exception, string $errorType): void
    {
        $userFacingMessage = $exception instanceof LotteryExecutionException
            ? $exception->getUserMessage()
            : __('lottery.execution_failed');

        Log::error('Lottery execution failed', [
            'lottery_id'   => $this->manifest->lotteryId,
            'project_id'   => $this->manifest->projectId,
            'error_type'   => $errorType,
            'user_message' => $userFacingMessage,
            'exception'    => get_class($exception),
            'message'      => $exception->getMessage(),
        ]);

        report($exception);
    }
}
