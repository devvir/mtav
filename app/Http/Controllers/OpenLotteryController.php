<?php

namespace App\Http\Controllers;

use App\Enums\LotteryRunStatus;
use App\Models\OpenLottery;
use App\Models\OpenLotteryItem;
use App\Models\OpenLotteryParticipant;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\OpenLotteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as Status;

/**
 * An open lottery's one page, and what people do on it. Nobody has an account: each browser holds
 * a secret for this lottery, in a cookie only its pages receive.
 */
class OpenLotteryController extends Controller
{
    private const A_YEAR = 525600;

    public function __construct(private OpenLotteryService $lotteries)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'min:2', 'max:80'],
            'items_name' => ['required', 'string', 'min:2', 'max:40'],
            'items'      => ['nullable', 'array', 'max:' . config('limits.open_lottery.items')],
            'items.*'    => ['required', 'string', 'max:60', 'distinct:ignore_case'],
        ]);

        [$lottery, $key] = $this->lotteries->create($data);

        $this->remember($lottery, 'organizer', $key);

        return to_route('open.show', $lottery);
    }

    public function show(Request $request, OpenLottery $openLottery): Response
    {
        $lottery = $openLottery;
        $run = $lottery->runs()->latest('id')->first();
        $me = $this->me($request, $lottery);
        $organizer = $this->organizes($request, $lottery);
        $decided = $run?->status === LotteryRunStatus::COMPLETED;
        $people = $lottery->participants()->with('item:id,name')->get();

        return inertia('Open/Lottery', [
            'lottery' => [
                'slug'       => $lottery->slug,
                'name'       => $lottery->name,
                'items_name' => $lottery->items_name,
                'link'       => route('open.show', $lottery),
                'kept_until' => $lottery->updated_at->addDays(config('limits.open_lottery.kept_days')),
            ],
            'state' => match (true) {
                ! $run                                        => 'open',
                $run->status === LotteryRunStatus::RUNNING    => 'running',
                $decided                                      => 'decided',
                default                                       => 'open',
            },
            'pace'    => $run?->status === LotteryRunStatus::RUNNING ? ['started_at' => $run->started_at, 'reveal_at' => $run->reveal_at] : null,
            'failure' => $run?->status === LotteryRunStatus::FAILED ? $run->failure : null,
            'items'   => $lottery->items()->get(['id', 'name']),
            'people'  => $people->map(fn (OpenLotteryParticipant $person) => [
                'id'     => $person->id,
                'name'   => $person->name,
                'ranked' => $person->ranked_at !== null,
                'item'   => $decided ? $person->item?->name : null,
            ]),
            'left_over' => $decided ? $lottery->items()->whereNotIn('id', $people->pluck('item_id')->filter())->pluck('name') : [],
            'me'        => $me && ! $me->removed_at ? [
                'id'      => $me->id,
                'ranking' => $me->ranking()->pluck('open_lottery_items.id'),
                'link'    => route('open.me', [$lottery, $this->cookie($request, $lottery, 'me')]),
            ] : null,
            'removed'   => (bool) $me?->removed_at,
            'organizer' => $organizer ? [
                'link'      => route('open.organize', [$lottery, $this->cookie($request, $lottery, 'organizer')]),
                'readiness' => $this->lotteries->readiness($lottery),
            ] : null,
        ]);
    }

    public function join(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:60']]);

        [, $key] = $this->lotteries->join($openLottery, $data['name']);

        $this->remember($openLottery, 'me', $key);

        return back();
    }

    public function leave(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $me = $this->me($request, $openLottery);

        abort_unless($me && ! $me->removed_at, Status::HTTP_FORBIDDEN);

        $this->lotteries->remove($openLottery, $me);
        Cookie::queue(Cookie::forget($this->cookieName($openLottery, 'me'), $this->path($openLottery)));

        return back();
    }

    public function rank(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $me = $this->me($request, $openLottery);

        abort_unless($me && ! $me->removed_at, Status::HTTP_FORBIDDEN);

        $data = $request->validate(['ranking' => ['present', 'array'], 'ranking.*' => ['integer', 'distinct']]);

        $this->lotteries->rank($openLottery, $me, $data['ranking']);

        return back();
    }

    /** One's personal link: this browser becomes theirs */
    public function returning(OpenLottery $openLottery, string $key): RedirectResponse
    {
        abort_unless(
            OpenLotteryParticipant::where('open_lottery_id', $openLottery->id)->where('key', OpenLottery::hashKey($key))->exists(),
            Status::HTTP_NOT_FOUND,
        );

        $this->remember($openLottery, 'me', $key);

        return to_route('open.show', $openLottery);
    }

    /** The organizer's link: this browser organizes too */
    public function organize(OpenLottery $openLottery, string $key): RedirectResponse
    {
        abort_unless($openLottery->isOrganizer($key), Status::HTTP_NOT_FOUND);

        $this->remember($openLottery, 'organizer', $key);

        return to_route('open.show', $openLottery);
    }

    public function addItem(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $this->ensureOrganizer($request, $openLottery);

        $this->lotteries->addItem($openLottery, $request->validate(['name' => ['required', 'string', 'max:60']])['name']);

        return back();
    }

    public function removeItem(Request $request, OpenLottery $openLottery, OpenLotteryItem $item): RedirectResponse
    {
        $this->ensureOrganizer($request, $openLottery);

        $this->lotteries->removeItem($openLottery, $item);

        return back();
    }

    public function removePerson(Request $request, OpenLottery $openLottery, OpenLotteryParticipant $participant): RedirectResponse
    {
        $this->ensureOrganizer($request, $openLottery);

        $this->lotteries->remove($openLottery, $participant);

        return back();
    }

    public function run(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $this->ensureOrganizer($request, $openLottery);

        $this->lotteries->run($openLottery, $request->boolean('confirmed'));

        return back();
    }

    /** What anyone may check once it decided: every ranking, what each got, the whole trail */
    public function record(OpenLottery $openLottery): JsonResponse
    {
        $run = $openLottery->runs()->where('status', LotteryRunStatus::COMPLETED)->firstOrFail();

        return response()->json([
            'lottery'   => $openLottery->only('name', 'items_name'),
            'run'       => $run->uuid,
            'finished'  => $run->finished_at,
            'solver'    => $run->solver,
            'people'    => $openLottery->participants()->with(['ranking:open_lottery_items.id,name', 'item:id,name'])->get()
                ->map(fn (OpenLotteryParticipant $person) => [
                    'name'    => $person->name,
                    'got'     => $person->item?->name,
                    'ranking' => $person->ranked_at ? $person->ranking->pluck('name') : null,
                ]),
            'audit' => $run->audits()->oldest('id')->get(['type', 'audit', 'created_at']),
        ], options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function me(Request $request, OpenLottery $lottery): ?OpenLotteryParticipant
    {
        $key = $this->cookie($request, $lottery, 'me');

        return $key ? OpenLotteryParticipant::where('open_lottery_id', $lottery->id)->where('key', OpenLottery::hashKey($key))->first() : null;
    }

    private function organizes(Request $request, OpenLottery $lottery): bool
    {
        return $lottery->isOrganizer($this->cookie($request, $lottery, 'organizer'));
    }

    private function ensureOrganizer(Request $request, OpenLottery $lottery): void
    {
        abort_unless($this->organizes($request, $lottery), Status::HTTP_FORBIDDEN);
    }

    private function remember(OpenLottery $lottery, string $who, string $key): void
    {
        Cookie::queue($this->cookieName($lottery, $who), $key, self::A_YEAR, $this->path($lottery));
    }

    private function cookie(Request $request, OpenLottery $lottery, string $who): ?string
    {
        return $request->cookie($this->cookieName($lottery, $who));
    }

    private function cookieName(OpenLottery $lottery, string $who): string
    {
        return "open_{$who}_{$lottery->slug}";
    }

    private function path(OpenLottery $lottery): string
    {
        return "/l/{$lottery->slug}";
    }
}
