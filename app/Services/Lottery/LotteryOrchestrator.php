<?php

namespace App\Services\Lottery;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Support\Collection;

/**
 * Solves a whole project's lottery from its manifest: one lottery per unit type,
 * then one more among the families and units left over by unbalanced types.
 *
 * Works on plain data (ids), never on models, and audits every step.
 */
class LotteryOrchestrator
{
    public function __construct(
        protected SolverInterface $solver,
        protected AuditService $audits,
    ) {
    }

    public function execute(LotteryManifest $manifest): ExecutionResult
    {
        $firstPass = collect($manifest->data)->map(
            fn (array $group) => $this->solve($manifest, $group['families'], $group['units'])
        );

        $results = $firstPass->push($this->distributeOrphans($manifest, $firstPass));

        $result = new ExecutionResult(
            $results->reduce(fn (array $picks, ExecutionResult $result) => $picks + $result->picks, []),
            $results->last()->orphans,
        );

        $this->audits->record($manifest, LotteryAuditType::PROJECT_EXECUTION, [
            'solver'  => get_class($this->solver),
            'picks'   => $result->picks,
            'orphans' => $result->orphans,
        ]);

        return $result;
    }

    /**
     * @param array<int, array<int>> $families  family_id => ranking
     * @param array<int> $units
     */
    protected function solve(LotteryManifest $manifest, array $families, array $units): ExecutionResult
    {
        if (! $families || ! $units) {
            return new ExecutionResult([], ['families' => array_keys($families), 'units' => $units]);
        }

        $result = $this->solver->execute($manifest, new LotterySpec($families, $units));

        $this->audits->record($manifest, LotteryAuditType::GROUP_EXECUTION, [
            'picks'   => $result->picks,
            'orphans' => $result->orphans,
        ]);

        return $result;
    }

    /**
     * Families and units left over by unbalanced types compete together, every
     * leftover unit being equally acceptable to every leftover family.
     *
     * @param Collection<ExecutionResult> $firstPass
     */
    protected function distributeOrphans(LotteryManifest $manifest, Collection $firstPass): ExecutionResult
    {
        $units = $firstPass->flatMap(fn (ExecutionResult $result) => $result->orphans['units'])->all();
        $families = $firstPass->flatMap(fn (ExecutionResult $result) => $result->orphans['families'])
            ->mapWithKeys(fn (int $familyId) => [$familyId => $units])
            ->all();

        return $this->solve($manifest, $families, $units);
    }
}
