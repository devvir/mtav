<?php

namespace App\Services;

use App\Events\OpenLotteryChanged;
use App\Jobs\PurgeStaleOpenLotteries;
use App\Models\LotteryRun;
use App\Models\OpenLottery;
use App\Models\OpenLotteryItem;
use App\Models\OpenLotteryParticipant;
use App\Services\Lottery\Contracts\LotteryHost;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\LotteryRuns;
use App\Services\Queue\Workers;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Open lotteries: anyone creates one, people join it by its link and rank what it distributes,
 * and its organizer runs it, once. The lottery itself is the same as any other.
 */
class OpenLotteryService implements LotteryHost
{
    public function __construct(private LotteryRuns $runs)
    {
    }

    /**
     * @param array{name: string, items_name: string, items?: list<string>} $data
     *
     * @return array{OpenLottery, string}  the lottery, and its organizer's secret
     */
    public function create(array $data): array
    {
        $key = Str::random(40);

        try {
            $lottery = DB::transaction(function () use ($data, $key) {
                $lottery = OpenLottery::create([
                    'slug'          => $this->freeSlug(),
                    'name'          => $data['name'],
                    'items_name'    => $data['items_name'],
                    'organizer_key' => OpenLottery::hashKey($key),
                ]);
                $lottery->items()->createMany(collect($data['items'] ?? [])->map(fn (string $name) => ['name' => $name])->all());

                return $lottery;
            });
        } catch (UniqueConstraintViolationException) {
            // Names that only differ in their accents are one name to the database
            throw ValidationException::withMessages(['items' => __('open_lottery.name_taken')]);
        }

        // No scheduler to rely on: creating one, a day apart at most, clears out the old
        Cache::add('open-lottery.purged', true, now()->addDay()) && PurgeStaleOpenLotteries::dispatch();

        return [$lottery, $key];
    }

    /** @return array{OpenLotteryParticipant, string}  who joined, and their secret */
    public function join(OpenLottery $lottery, string $name): array
    {
        $key = Str::random(40);

        $participant = $this->whileOpen($lottery, function () use ($lottery, $name, $key) {
            if ($lottery->participants()->count() >= config('limits.open_lottery.participants')) {
                throw ValidationException::withMessages(['name' => __('open_lottery.full')]);
            }

            $this->ensureNameFree($lottery->participants(), $name, 'name');

            return $lottery->participants()->create(['name' => $name, 'key' => OpenLottery::hashKey($key)]);
        });

        return [$participant, $key];
    }

    /** Taking oneself out, or being taken out by the organizer: told so if they come back */
    public function remove(OpenLottery $lottery, OpenLotteryParticipant $participant): void
    {
        $this->whileOpen($lottery, fn () => $participant->update(['removed_at' => now()]));
    }

    public function addItem(OpenLottery $lottery, string $name): void
    {
        $this->whileOpen($lottery, function () use ($lottery, $name) {
            if ($lottery->items()->count() >= config('limits.open_lottery.items')) {
                throw ValidationException::withMessages(['name' => __('open_lottery.too_many_items')]);
            }

            $this->ensureNameFree($lottery->items(), $name, 'name');

            try {
                $lottery->items()->create(['name' => $name]);
            } catch (UniqueConstraintViolationException) {
                // Names the database takes for one, whatever the check above made of them
                throw ValidationException::withMessages(['name' => __('open_lottery.name_taken')]);
            }
        });
    }

    public function removeItem(OpenLottery $lottery, OpenLotteryItem $item): void
    {
        $this->whileOpen($lottery, fn () => $item->delete());
    }

    /** @param list<int> $itemIds  most wanted first; those left out follow in a fixed order */
    public function rank(OpenLottery $lottery, OpenLotteryParticipant $participant, array $itemIds): void
    {
        $this->whileOpen($lottery, function () use ($lottery, $participant, $itemIds) {
            // An item removed while they were ranking leaves the ranking; the rest keep their order
            $known = $lottery->items()->whereIn('id', $itemIds)->pluck('id')->all();
            $itemIds = array_values(array_filter($itemIds, fn (int $id) => in_array($id, $known, true)));

            $participant->ranking()->sync(
                collect($itemIds)->values()->mapWithKeys(fn (int $id, int $i) => [$id => ['rank' => $i + 1]])->all()
            );
            $participant->update(['ranked_at' => now()]);
        });
    }

    /** What it takes to run, for the organizer to see and confirm */
    public function readiness(OpenLottery $lottery): array
    {
        $people = $lottery->participants()->get(['id', 'name', 'ranked_at']);
        $items = $lottery->items()->count();

        return [
            'unranked'  => $people->whereNull('ranked_at')->pluck('name')->values()->all(),
            'left_out'  => max(0, $people->count() - $items),
            'left_over' => max(0, $items - $people->count()),
            'runnable'  => $people->count() >= 2 && $items >= 1 && Workers::running(),
            'working'   => Workers::running(),
        ];
    }

    /** @throws ValidationException when it may not run, or something unusual was not confirmed */
    public function run(OpenLottery $lottery, bool $confirmed): LotteryRun
    {
        $run = $this->runs->start($lottery->runs(), function () use ($lottery, $confirmed) {
            OpenLottery::whereKey($lottery->id)->lockForUpdate()->first();

            if (! $lottery->isOpen()) {
                throw ValidationException::withMessages(['run' => __('open_lottery.closed')]);
            }

            $ready = $this->readiness($lottery);

            if (! $ready['runnable']) {
                throw ValidationException::withMessages(['run' => __($ready['working'] ? 'open_lottery.not_enough' : 'open_lottery.no_worker')]);
            }

            if (! $confirmed && ($ready['unranked'] || $ready['left_out'] || $ready['left_over'])) {
                throw ValidationException::withMessages(['run' => __('open_lottery.confirm')]);
            }
        });

        $this->changed($lottery);

        return $run;
    }

    /** What a run decides from: everyone's whole ranking, in one group */
    public function manifest(LotteryRun $run): LotteryManifest
    {
        $lottery = $run->openLottery;
        $items = $lottery->items()->pluck('id')->all();

        $participants = $lottery->participants()->with('ranking:open_lottery_items.id')->get()
            ->mapWithKeys(fn (OpenLotteryParticipant $person) => [$person->id => $this->wholeRanking($person, $items)]);

        return new LotteryManifest($run->uuid, null, $run->id, [
            0 => ['participants' => $participants->all(), 'items' => $items],
        ]);
    }

    /** Each person gets their item */
    public function assign(LotteryRun $run, ExecutionResult $result): void
    {
        foreach ($result->picks as $participantId => $itemId) {
            OpenLotteryParticipant::whereKey($participantId)->update(['item_id' => $itemId]);
        }
    }

    /** Pages open on it show who got what */
    public function completed(LotteryRun $run): void
    {
        $this->changed($run->openLottery);
    }

    /** It decided nothing: the organizer may try again */
    public function failed(LotteryRun $run): void
    {
        $this->changed($run->openLottery);
    }

    /** Pages open on it ask again; a down websocket server stops nothing */
    public function changed(OpenLottery $lottery): void
    {
        $lottery->touch();
        rescue(fn () => OpenLotteryChanged::dispatch($lottery->slug), report: false);
    }

    /** Their ranking, then what they left out, in an order fixed for each of them */
    private function wholeRanking(OpenLotteryParticipant $person, array $items): array
    {
        $ranked = $person->ranking->pluck('id')->all();

        $rest = collect(array_diff($items, $ranked))
            ->sortBy(fn (int $item) => hash('sha256', config('app.key') . "|{$person->id}|{$item}"))
            ->values()->all();

        return [...$ranked, ...$rest];
    }

    /** @throws ValidationException once it ran: from then on nothing in it changes */
    private function whileOpen(OpenLottery $lottery, callable $change): mixed
    {
        $result = DB::transaction(function () use ($lottery, $change) {
            OpenLottery::whereKey($lottery->id)->lockForUpdate()->first();

            if (! $lottery->isOpen()) {
                throw ValidationException::withMessages(['lottery' => __('open_lottery.closed')]);
            }

            return $change();
        });

        $this->changed($lottery);

        return $result;
    }

    private function ensureNameFree($relation, string $name, string $field): void
    {
        if ($relation->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            throw ValidationException::withMessages([$field => __('open_lottery.name_taken')]);
        }
    }

    private function freeSlug(): string
    {
        do {
            $slug = Str::random(10);
        } while (OpenLottery::where('slug', $slug)->exists());

        return $slug;
    }
}
