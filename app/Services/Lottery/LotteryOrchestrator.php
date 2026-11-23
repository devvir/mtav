<?php

namespace App\Services\Lottery;

use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Support\Collection;
use Random\Engine\Xoshiro256StarStar;
use Random\Randomizer;

/**
 * Solves a whole project's lottery from its manifest: one lottery per group,
 * then one more among the participants and items left over by unbalanced groups.
 *
 * Works on plain data (ids), never on models, and audits every step. Ties are drawn: the
 * order in which participants and items reach the solver is shuffled with the run's uuid as
 * the seed, so equals are equally likely to win and a run reads back the same from its record.
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
            fn (array $group, int|string $key) => $this->solve($manifest, $group['participants'], $group['items'], (string) $key)
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
     * @param string $draw  which lottery of the run this is (its group, or the leftovers), so each gets its own order
     */
    protected function solve(LotteryManifest $manifest, array $participants, array $items, string $draw): ExecutionResult
    {
        if (! $participants || ! $items) {
            return new ExecutionResult([], ['participants' => array_keys($participants), 'items' => $items]);
        }

        $result = $this->solver->execute($manifest, $this->drawn($manifest, $draw, $participants, $items));

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

        return $this->solve($manifest, $participants, $items, 'leftovers');
    }

    /**
     * The spec in an order drawn for this lottery. Among equally good assignments the solver
     * keeps the first it meets, which depends on the order participants and items come in: that
     * order is the lottery's die. The seed is the run's uuid, recorded in its manifest, so two
     * participants with the same ranking are equally likely to win, and the run reads back the
     * same way, with nothing more to store.
     *
     * @param array<int, array<int>> $participants  participant_id => ranking
     * @param array<int> $items
     */
    protected function drawn(LotteryManifest $manifest, string $draw, array $participants, array $items): LotterySpec
    {
        $die = new Randomizer(new Xoshiro256StarStar(hash('sha256', "{$manifest->uuid}:{$draw}", true)));
        $order = $die->shuffleArray(array_keys($participants));

        return new LotterySpec(
            array_combine($order, array_map(fn ($id) => $participants[$id], $order)),
            $die->shuffleArray($items),
        );
    }
}
