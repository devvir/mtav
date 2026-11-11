<?php

namespace App\Http\OpenLottery;

use App\Enums\LotteryRunStatus;
use App\Models\LotteryRun;
use App\Models\OpenLottery;
use App\Models\OpenLotteryParticipant;
use App\Services\OpenLotteryService;

/**
 * An open lottery's page, as this browser sees it: the same for everyone, plus what is theirs
 * (their ranking and link) and, for its organizer, what it takes to run.
 */
class OpenLotteryPage
{
    public function __construct(private OpenLotteryService $lotteries)
    {
    }

    public function props(OpenLottery $lottery, OpenLotteryVisitor $visitor): array
    {
        $run = $lottery->runs()->latest('id')->first();
        $decided = $run?->status === LotteryRunStatus::COMPLETED;
        $people = $lottery->participants()->with('item:id,name')->get();
        $me = $visitor->takingPart();

        return [
            'lottery' => [
                'slug'       => $lottery->slug,
                'name'       => $lottery->name,
                'items_name' => $lottery->items_name,
                'link'       => route('open.show', $lottery),
                'kept_until' => $lottery->updated_at->addDays(config('limits.open_lottery.kept_days')),
            ],
            'state'   => $this->state($run),
            'pace'    => $run?->status === LotteryRunStatus::RUNNING ? ['started_at' => $run->started_at, 'reveal_at' => $run->reveal_at] : null,
            'failure' => $run?->status === LotteryRunStatus::FAILED ? $run->failureMessage() : null,
            'items'   => $lottery->items()->get(['id', 'name']),
            'people'  => $people->map(fn (OpenLotteryParticipant $person) => [
                'id'     => $person->id,
                'name'   => $person->name,
                'ranked' => $person->ranked_at !== null,
                'item'   => $decided ? $person->item?->name : null,
            ]),
            'left_over' => $decided ? $lottery->items()->whereNotIn('id', $people->pluck('item_id')->filter())->pluck('name') : [],
            'me'        => $me ? [
                'id'      => $me->id,
                'ranking' => $me->ranking()->pluck('open_lottery_items.id'),
                'link'    => route('open.me', [$lottery, $visitor->participantKey()]),
            ] : null,
            'removed'   => $visitor->wasRemoved(),
            'organizer' => $visitor->organizes() ? [
                'link'      => route('open.organize', [$lottery, $visitor->organizerKey()]),
                'readiness' => $this->lotteries->readiness($lottery),
            ] : null,
        ];
    }

    /** A failed run decided nothing: the lottery is open again */
    private function state(?LotteryRun $run): string
    {
        return match ($run?->status) {
            LotteryRunStatus::RUNNING   => 'running',
            LotteryRunStatus::COMPLETED => 'decided',
            default                     => 'open',
        };
    }
}
