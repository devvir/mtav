<?php

use App\Enums\LotteryRunStatus;
use App\Jobs\PublishLotteryRun;
use App\Jobs\RunLottery;
use App\Models\LotteryRun;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\LotteryRuns;
use App\Services\OpenLotteryService;
use Illuminate\Support\Facades\Queue;

/**
 * A run's life is the same whatever the lottery belongs to, a project or an open lottery: what
 * it decides from is recorded as it starts, its result waits for the reveal, it completes once,
 * and a failure closes only a run still running.
 */

/** A host with two participants and two items: its runs, and an assignment it may receive */
dataset('hosts', [
    'a project' => function () {
        $project = aProject();
        $type = aUnitType($project);
        $units = [aUnit($type), aUnit($type)];
        $families = [aFamily($project, $type), aFamily($project, $type)];
        aMemberOf($families[0]);
        aMemberOf($families[1]);

        return [
            'runs'     => fn () => $project->lotteryRuns(),
            'picks'    => [$families[0]->id => $units[1]->id, $families[1]->id => $units[0]->id],
            'assigned' => fn () => $project->units()->whereNotNull('family_id')->pluck('id', 'family_id')->all(),
        ];
    },
    'an open lottery' => function () {
        $service = app(OpenLotteryService::class);
        [$lottery] = $service->create(['name' => 'August', 'items_name' => 'Rooms', 'items' => ['Blue room', 'Green room']]);
        [$ana] = $service->join($lottery, 'Ana');
        [$beto] = $service->join($lottery, 'Beto');
        [$blue, $green] = $lottery->items()->get()->all();

        return [
            'runs'     => fn () => $lottery->runs(),
            'picks'    => [$ana->id => $green->id, $beto->id => $blue->id],
            'assigned' => fn () => $lottery->participants()->whereNotNull('item_id')->pluck('item_id', 'id')->all(),
        ];
    },
]);

function aRunOf(array $host, array $attributes = []): LotteryRun
{
    return app(LotteryRuns::class)->start($host['runs'](), fn () => null, $attributes);
}

beforeEach(function () {
    Queue::fake([RunLottery::class, PublishLotteryRun::class]);
});

describe('Starting a run', function () {
    it('writes it running and records, with it, what it decides from', function (array $host) {
        $run = aRunOf($host);

        $init = $run->audits()->where('type', LotteryAuditType::INIT)->sole()->audit;
        expect($run->status)->toBe(LotteryRunStatus::RUNNING)
            ->and($run->solver)->toBe(config('lottery.default'))
            ->and($run->started_at)->not->toBeNull()
            ->and($run->reveal_at)->not->toBeNull()
            ->and($init['manifest'])->toEqual(json_decode(json_encode($run->host()->manifest($run)), true));
        Queue::assertPushed(RunLottery::class, fn ($job) => $job->run->is($run));
    })->with('hosts');

    it('writes nothing when its host refuses it', function (array $host) {
        $refusing = fn () => throw new RuntimeException('Not now');

        expect(fn () => app(LotteryRuns::class)->start($host['runs'](), $refusing))->toThrow(RuntimeException::class, 'Not now');

        expect($host['runs']()->exists())->toBeFalse()
            ->and(App\Models\LotteryAudit::exists())->toBeFalse();
        Queue::assertNothingPushed();
    })->with('hosts');
});

describe('A run solved', function () {
    it('holds its result until the reveal', function (array $host) {
        $run = aRunOf($host, ['reveal_at' => now()->addMinute()]);

        app(LotteryRuns::class)->solved($run, new ExecutionResult($host['picks'], []));

        expect($run->fresh()->status)->toBe(LotteryRunStatus::RUNNING)
            ->and($run->fresh()->result['picks'])->toEqual($host['picks'])
            ->and($host['assigned']())->toBe([]);
        Queue::assertPushed(PublishLotteryRun::class, fn ($job) => $job->delay->equalTo($run->reveal_at));
    })->with('hosts');

    it('completes at once when the reveal has come, letting the held result go', function (array $host) {
        $run = aRunOf($host, ['reveal_at' => now()->subSecond()]);

        app(LotteryRuns::class)->solved($run, new ExecutionResult($host['picks'], []));

        expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED)
            ->and($run->fresh()->finished_at)->not->toBeNull()
            ->and($run->fresh()->result)->toBeNull()
            ->and($host['assigned']())->toEqual($host['picks']);
        Queue::assertNotPushed(PublishLotteryRun::class);
    })->with('hosts');
});

describe('A run failing', function () {
    it('closes it once, with its reason, however many times it is told', function (array $host) {
        $run = aRunOf($host);

        app(LotteryRuns::class)->fail($run, 'lottery.execution_failed', debug: ['error' => 'first']);
        app(LotteryRuns::class)->fail($run, 'lottery.execution_failed', debug: ['error' => 'second']);

        expect($run->fresh()->status)->toBe(LotteryRunStatus::FAILED)
            ->and($run->fresh()->failureMessage())->toBe(__('lottery.execution_failed'))
            ->and($run->audits()->where('type', LotteryAuditType::FAILURE)->pluck('audit')->pluck('error')->all())->toBe(['first']);
    })->with('hosts');

    it('leaves a completed run as it is', function (array $host) {
        $run = aRunOf($host, ['reveal_at' => now()->subSecond()]);
        app(LotteryRuns::class)->solved($run, new ExecutionResult($host['picks'], []));

        app(LotteryRuns::class)->fail($run, 'lottery.execution_failed');

        expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED)
            ->and($run->audits()->where('type', LotteryAuditType::FAILURE)->exists())->toBeFalse()
            ->and($host['assigned']())->toEqual($host['picks']);
    })->with('hosts');
});
