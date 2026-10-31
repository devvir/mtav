<?php

namespace App\Services\Lottery;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Support\Collection;

/**
 * Solves a whole project's lottery from its manifest: one lottery per group,
 * then one more among the participants and items left over by unbalanced groups.
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
            fn (array $group) => $this->solve($manifest, $group['participants'], $group['items'])
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
     * @param array<int, array<int>> $participants  participant_id => ranking
     * @param array<int> $items
     */
    protected function solve(LotteryManifest $manifest, array $participants, array $items): ExecutionResult
    {
        if (! $participants || ! $items) {
            return new ExecutionResult([], ['participants' => array_keys($participants), 'items' => $items]);
        }

        $result = $this->solver->execute($manifest, new LotterySpec($participants, $items));

        $this->audits->record($manifest, LotteryAuditType::GROUP_EXECUTION, [
            'picks'   => $result->picks,
            'orphans' => $result->orphans,
        ]);

        return $result;
    }

    /**
     * Participants and items left over by unbalanced groups are matched together, every
     * leftover item being equally acceptable to every leftover participant.
     *
     * @param Collection<ExecutionResult> $firstPass
     */
    protected function distributeOrphans(LotteryManifest $manifest, Collection $firstPass): ExecutionResult
    {
        $items = $firstPass->flatMap(fn (ExecutionResult $result) => $result->orphans['items'])->all();
        $participants = $firstPass->flatMap(fn (ExecutionResult $result) => $result->orphans['participants'])
            ->mapWithKeys(fn (int $participantId) => [$participantId => $items])
            ->all();

        return $this->solve($manifest, $participants, $items);
    }
}
