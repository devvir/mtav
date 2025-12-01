<?php

namespace App\Services\Lottery;

use App\Events\Lottery\GroupLotteryExecuted;
use App\Events\Lottery\ProjectLotteryExecuted;
use App\Services\Lottery\Contracts\ExecutorInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Orchestrates the multi-phase lottery execution process.
 *
 * This service operates at a lower abstraction level than ExecutionService, dealing with
 * raw data structures (lists of numbers -- IDs), not Eloquent models.
 */
class LotteryOrchestrator
{
    protected string $uuid;

    protected function __construct(
        protected ExecutorInterface $executor,
        protected LotteryManifest $manifest,
    ) {
        $this->uuid = (string) Str::uuid();
    }

    /**
     * @param ExecutorInterface $executor The strategy for executing individual lotteries
     * @param LotteryManifest $manifest Complete lottery manifest with all unit types
     */
    public static function make(ExecutorInterface $executor, LotteryManifest $manifest): self
    {
        return new self($executor, $manifest);
    }

    /**
     * Orchestrate the complete lottery execution across all phases.
     */
    public function execute(): ExecutionResult
    {
        $groups = $this->manifest->data;

        $firstPassResults = collect($groups)->map($this->executeLottery(...));
        $secondChanceResults = $this->distributeOrphans($firstPassResults);

        $results = $firstPassResults->add($secondChanceResults);

        return $this->reportResults($results);
    }

    /**
     * Execute lottery for a given group of families (with picks) and units.
     */
    protected function executeLottery(array $data): ExecutionResult
    {
        $spec = new LotterySpec($data['families'], $data['units']);
        $result = $this->executor->execute($spec);

        GroupLotteryExecuted::dispatch(
            $this->uuid,
            $this->manifest,
            $this->executor,
            $result
        );

        return $result;
    }

    /**
     * Distribute remnant families and units, from previous unbalanced groups' executions.
     */
    protected function distributeOrphans(Collection $firstPassResults): ExecutionResult
    {
        $orphansGroup = [
            'units'    => $firstPassResults->flatMap(fn ($result) => $result->orphans['units'])->all(),
            'families' => $firstPassResults->flatMap(fn ($result) => $result->orphans['families'])
                ->mapWithKeys(fn ($familyId) => [$familyId => []])->all(),
        ];

        return $this->executeLottery($orphansGroup);
    }

    /**
     * Report execution results.
     */
    protected function reportResults(Collection $results): ExecutionResult
    {
        $picks = $results->mapWithKeys(fn ($result) => $result->picks)->all();
        $orphans = $results->flatMap(fn ($result) => $result->orphans)->all();

        $report = new ExecutionResult($picks, $orphans);

        Log::info('Lottery execution completed', [
            'project_id'      => $this->manifest->projectId,
            'total_picks'     => count($report->picks),
            'orphan_families' => count($report->orphans['families']),
            'orphan_units'    => count($report->orphans['units']),
        ]);

        ProjectLotteryExecuted::dispatch(
            $this->uuid,
            $this->manifest,
            $this->executor,
            $report
        );

        return $report;
    }
}
