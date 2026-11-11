<?php

namespace App\Http\Controllers;

use App\Enums\LotteryRunStatus;
use App\Http\OpenLottery\OpenLotteryPage;
use App\Http\OpenLottery\OpenLotteryVisitor;
use App\Models\OpenLottery;
use App\Models\OpenLotteryItem;
use App\Models\OpenLotteryParticipant;
use App\Services\Lottery\RunRecord;
use App\Services\OpenLotteryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as Status;

/**
 * An open lottery's one page, and what people do on it. Nobody has an account: who a browser is
 * comes from the secrets it holds for this lottery (`OpenLotteryVisitor`).
 */
class OpenLotteryController extends Controller
{
    public function __construct(private OpenLotteryService $lotteries)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        // How many first, on its own: a rule on each item (distinct compares each with every
        // other) would read them all, however many anyone sent
        $request->validate(['items' => ['nullable', 'array', 'max:' . config('limits.open_lottery.items')]]);
        $data = $request->validate([
            'name'       => ['required', 'string', 'min:2', 'max:80'],
            'items_name' => ['required', 'string', 'min:2', 'max:40'],
            'items'      => ['nullable', 'array'],
            'items.*'    => ['required', 'string', 'max:60', 'distinct:ignore_case'],
        ]);

        [$lottery, $key] = $this->lotteries->create($data);

        $this->visitor($request, $lottery)->rememberOrganizer($key);

        return to_route('open.show', $lottery);
    }

    public function show(Request $request, OpenLottery $openLottery, OpenLotteryPage $page): Response
    {
        return inertia('Open/Lottery', $page->props($openLottery, $this->visitor($request, $openLottery)));
    }

    public function join(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:60']]);

        [, $key] = $this->lotteries->join($openLottery, $data['name']);

        $this->visitor($request, $openLottery)->rememberParticipant($key);

        return back();
    }

    public function leave(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $visitor = $this->visitor($request, $openLottery);
        $me = $visitor->takingPart() ?? abort(Status::HTTP_FORBIDDEN);

        $this->lotteries->remove($openLottery, $me);
        $visitor->forgetParticipant();

        return back();
    }

    public function rank(Request $request, OpenLottery $openLottery): RedirectResponse
    {
        $me = $this->visitor($request, $openLottery)->takingPart() ?? abort(Status::HTTP_FORBIDDEN);

        // How many first, on its own, as for the items
        $request->validate(['ranking' => ['present', 'array', 'max:' . config('limits.open_lottery.items')]]);
        $data = $request->validate(['ranking' => ['present', 'array'], 'ranking.*' => ['integer', 'distinct']]);

        $this->lotteries->rank($openLottery, $me, $data['ranking']);

        return back();
    }

    /** One's personal link: this browser becomes theirs */
    public function returning(Request $request, OpenLottery $openLottery, string $key): RedirectResponse
    {
        abort_unless(OpenLotteryParticipant::whereBelongsTo($openLottery, 'lottery')->byKey($key)->exists(), Status::HTTP_NOT_FOUND);

        $this->visitor($request, $openLottery)->rememberParticipant($key);

        return to_route('open.show', $openLottery);
    }

    /** The organizer's link: this browser organizes too */
    public function organize(Request $request, OpenLottery $openLottery, string $key): RedirectResponse
    {
        abort_unless($openLottery->isOrganizer($key), Status::HTTP_NOT_FOUND);

        $this->visitor($request, $openLottery)->rememberOrganizer($key);

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
            'lottery'     => $openLottery->only('name', 'items_name'),
            'run'         => $run->uuid,
            'finished'    => $run->finished_at,
            'solver'      => $run->solver,
            'fingerprint' => (new RunRecord($run))->fingerprint(),
            'people'      => $openLottery->participants()->with(['ranking:open_lottery_items.id,name', 'item:id,name'])->get()
                ->map(fn (OpenLotteryParticipant $person) => [
                    'name'    => $person->name,
                    'got'     => $person->item?->name,
                    'ranking' => $person->ranked_at ? $person->ranking->pluck('name') : null,
                ]),
            'audit' => $run->audits()->oldest('id')->get(['type', 'audit', 'created_at']),
        ], options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function visitor(Request $request, OpenLottery $lottery): OpenLotteryVisitor
    {
        return new OpenLotteryVisitor($request, $lottery);
    }

    private function ensureOrganizer(Request $request, OpenLottery $lottery): void
    {
        abort_unless($this->visitor($request, $lottery)->organizes(), Status::HTTP_FORBIDDEN);
    }
}
