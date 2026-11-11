<?php

use App\Jobs\RunLottery;
use App\Enums\LotteryRunStatus;
use App\Models\Event;
use App\Models\Notification;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Once the lottery is due, an admin runs it: each family gets one unit of its type,
 * chosen to make the worst-off family as well off as possible first, then the whole
 * project as satisfied as possible. The outcome is final and audited; invalidating
 * it is exceptional and visible to everyone.
 */

beforeEach(function () {

    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
});

/**
 * Families of the project's type, each ranking the given units (by index) in order.
 *
 * @return array{0: \Illuminate\Support\Collection, 1: \Illuminate\Support\Collection}
 */
function familiesRanking(array $rankings): array
{
    $test = test();
    $units = collect(range(1, count($rankings)))->map(fn () => aUnit($test->type));

    $families = collect($rankings)->map(function (array $ranking) use ($test, $units) {
        $family = aFamily($test->project, $test->type);
        $member = aMemberOf($family);

        $test->as($member, $test->project)->put(route('lottery.preferences'), [
            'preferences' => collect($ranking)->map(fn ($i) => $units[$i]->id)->all(),
        ])->assertSessionHasNoErrors();

        return $family;
    });

    return [$families, $units];
}

function lotteryIsDue(): Event
{
    return tap(test()->project->lottery)->update(['start_date' => now()->subMinute()]);
}

function runLottery(array $options = [])
{
    $test = test();

    return $test->as($test->admin, $test->project)
        ->post(route('manage.lottery.run'), ['options' => $options]);
}

function assignment(iterable $families): array
{
    return collect($families)->map(fn ($family) => $family->fresh()->unit?->id)->all();
}

describe('An admin', function () {
    it('runs a due lottery: every family gets exactly one unit of its type', function () {
        [$families, $units] = familiesRanking([[0, 1, 2], [1, 2, 0], [2, 0, 1]]);
        lotteryIsDue();

        runLottery()->assertSessionHasNoErrors();

        expect(assignment($families))->toEqualCanonicalizing($units->pluck('id')->all());
    });

    it('gives each family its first choice when choices do not collide', function () {
        [$families, $units] = familiesRanking([[0, 1, 2], [1, 2, 0], [2, 0, 1]]);
        lotteryIsDue();

        runLottery();

        expect(assignment($families))->toBe($units->pluck('id')->all());
    });

    it('protects the worst-off family first', function () {
        // A and B both want unit 0 most; C wants unit 1. The only assignment where no
        // family ends below its 2nd choice is A:0, B:2, C:1.
        [$families, $units] = familiesRanking([[0, 1, 2], [0, 2, 1], [1, 0, 2]]);
        lotteryIsDue();

        runLottery();

        expect(assignment($families))->toBe([$units[0]->id, $units[2]->id, $units[1]->id]);
    });

    it('cannot run the lottery before its scheduled date', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        $this->project->lottery->update(['start_date' => now()->addDay()]);

        runLottery();

        expect(assignment($families))->each->toBeNull();
    });

    it('runs the lottery only once', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $first = assignment($families);

        runLottery();

        expect(assignment($families))->toBe($first)
            ->and($this->project->lotteryRuns()->count())->toBe(1);
    });

    it('must confirm running a lottery with more units than families', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        aUnit($this->type); // A spare unit
        lotteryIsDue();

        runLottery()->assertSessionHas('confirm');
        expect(assignment($families))->each->toBeNull();

        runLottery(['mismatch-allowed'])->assertSessionHasNoErrors();
        expect(assignment($families))->each->not->toBeNull();
    });

    it('keeps an audit of what the lottery considered and decided', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();

        runLottery();

        $audits = $this->project->lotteryRuns()->sole()->audits()->pluck('type')->map->value->all();

        expect($audits)->toContain('init', 'group_execution', 'project_execution');
    });

    it('invalidates a completed lottery, giving a reason every member can read', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        $member = $families[0]->members()->first();
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();

        $this->as($this->admin, $this->project)
            ->post(route('manage.lottery.invalidate', $run), ['reason' => 'A family was missing from the draw'])
            ->assertSessionHasNoErrors();

        expect(assignment($families))->each->toBeNull();

        $runs = $this->as($member, $this->project)->get(route('lottery.show'))->inertiaProps('runs');

        expect($runs[0])
            ->status->toBe('invalidated')
            ->invalidation_reason->toBe('A family was missing from the draw')
            ->invalidated_by->toBe($this->admin->name);

        $seen = $this->as($member)->get(route('notifications.index'))->inertiaProps('notifications.data');
        expect(collect($seen)->pluck('title'))->toContain(__('notifications.lottery_invalidated_title'));
    });

    it('cannot invalidate without a reason', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();

        $this->as($this->admin, $this->project)
            ->post(route('manage.lottery.invalidate', $this->project->lotteryRuns()->sole()), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        expect($this->project->lotteryRuns()->sole()->status->value)->toBe('completed');
    });

    it('runs the lottery again after an invalidation, keeping the earlier run on record', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $this->as($this->admin, $this->project)->post(
            route('manage.lottery.invalidate', $this->project->lotteryRuns()->sole()),
            ['reason' => 'A family was missing from the draw'],
        );

        runLottery()->assertSessionHasNoErrors();

        expect($this->project->lotteryRuns()->pluck('status')->map->value->all())
            ->toEqualCanonicalizing(['invalidated', 'completed'])
            ->and(assignment($families))->each->not->toBeNull();
    });

    it('cannot run an unapproved project\'s lottery beyond its size limit', function () {
        config()->set('limits.unapproved.families', 2);
        $this->project = anUnapprovedProject();
        $this->admin = $this->project->admins()->first();
        $this->type = aUnitType($this->project);
        [$families] = familiesRanking([[0, 1, 2], [1, 2, 0], [2, 0, 1]]);
        lotteryIsDue();

        runLottery()->assertSessionHas('error');

        expect(assignment($families))->each->toBeNull();
    });
    it('closes a run that fails, tells whoever started it, and lets the lottery run again', function () {
        $solver = config('lottery.default');
        config()->set('lottery.solvers.broken', ['solver' => BrokenSolver::class]);
        config()->set('lottery.default', 'broken');
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();

        runLottery();

        $run = test()->project->lotteryRuns()->latest('id')->first();
        expect($run->status)->toBe(LotteryRunStatus::FAILED)
            ->and($run->failureMessage())->toBe(__('lottery.execution_failed'))
            ->and(assignment($families))->toBe([null, null])
            ->and(Notification::for(test()->admin)->where('target_id', test()->admin->id)->exists())->toBeTrue();

        config()->set('lottery.default', $solver);
        runLottery()->assertSessionHasNoErrors();

        expect(assignment($families))->not->toContain(null);
    });

    it('closes a run whose job died (timed out, or its worker stopped), so the lottery can run again', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        $run = test()->project->lotteryRuns()->create([
            'uuid' => (string) Str::uuid(), 'status' => LotteryRunStatus::RUNNING, 'solver' => config('lottery.default'), 'started_by' => test()->admin->id, 'started_at' => now(),
        ]);

        (new RunLottery($run))->failed(new RuntimeException('Timed out'));

        expect($run->fresh()->status)->toBe(LotteryRunStatus::FAILED);
        runLottery()->assertSessionHasNoErrors();
    });

    it('holds units and families still while a run is being solved', function () {
        [$families, $units] = familiesRanking([[0, 1], [1, 0]]);
        test()->project->lotteryRuns()->create([
            'uuid' => (string) Str::uuid(), 'status' => LotteryRunStatus::RUNNING, 'solver' => config('lottery.default'), 'started_at' => now(),
        ]);
        $this->as($this->admin, $this->project);

        $this->delete(route('manage.units.destroy', $units[0]))->assertSessionHasErrors('lottery');
        $this->post(route('manage.units.store'), ['identifier' => 'Z-1', 'unit_type_id' => $this->type->id])->assertSessionHasErrors('lottery');
        $this->delete(route('manage.families.destroy', $families[0]))->assertSessionHasErrors('lottery');

        expect($units[0]->fresh())->not->toBeNull()->and($families[0]->fresh())->not->toBeNull();
    });

    it('says why it failed in the language of whoever reads it, whatever the worker spoke', function () {
        $run = aLotteryInProgress($this->project);

        app(App\Services\LotteryService::class)->fail($run, 'lottery.glpk_timeout');

        $inEach = collect(['en', 'es_UY', 'fr'])->mapWithKeys(fn ($locale) => [$locale => (function () use ($locale, $run) {
            app()->setLocale($locale);

            return App\Http\Resources\LotteryRunResource::make($run->fresh())->resolve(request())['failure'];
        })()]);

        expect($inEach['en'])->toBe(trans('lottery.glpk_timeout', [], 'en'))
            ->and($inEach['es_UY'])->toBe(trans('lottery.glpk_timeout', [], 'es_UY'))
            ->and($inEach['fr'])->toBe(trans('lottery.glpk_timeout', [], 'fr'))
            ->and($inEach['es_UY'])->not->toBe($inEach['en']);
    });

    it('keeps the words a solver gave as its reason, blanks and all, to say later', function () {
        config()->set('lottery.solvers.broken', ['solver' => SolverGivingReason::class]);
        config()->set('lottery.default', 'broken');
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();

        runLottery();

        $run = $this->project->lotteryRuns()->latest('id')->first();

        expect($run->status)->toBe(LotteryRunStatus::FAILED)
            ->and(json_decode($run->failure, true))->toBe(['key' => 'lottery.cannot_run_x', 'params' => ['count' => 3]]);
    });

    it('shows as it is a reason kept whole before reasons were kept as words to say', function () {
        $run = aLotteryInProgress($this->project);
        $run->update(['status' => LotteryRunStatus::FAILED, 'failure' => 'No se pudo.']);

        expect($run->fresh()->failureMessage())->toBe('No se pudo.');
    });

    it('leaves a run that finished as it is, whatever fails after it', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $run = test()->project->lotteryRuns()->latest('id')->first();

        (new RunLottery($run))->failed(new RuntimeException('Mail down'));

        expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED);
    });
});

describe('A member', function () {
    it('cannot run the lottery', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();

        expect($this->as($families[0]->members()->first(), $this->project)->post(route('manage.lottery.run')))->toBeDenied();

        expect(assignment($families))->each->toBeNull();
    });

    it('sees which unit every family received', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();

        $seen = collect($this->as($families[0]->members()->first(), $this->project)
            ->get(route('lottery.show'))->inertiaProps('assignment'));

        foreach ($families as $family) {
            expect($seen->firstWhere('id', $family->id)['unit']['id'] ?? null)
                ->toBe($family->fresh()->unit->id);
        }
    });
});

/** A solver that fails saying why, in words of the app's */
class SolverGivingReason implements SolverInterface
{
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        throw App\Services\Lottery\Exceptions\LotteryExecutionException::withUserMessage('lottery.cannot_run_x', ['count' => 3]);
    }
}

/** A solver that breaks, as an unreachable or crashing one would */
class BrokenSolver implements SolverInterface
{
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        throw new RuntimeException('The solver crashed');
    }
}

describe('Its record', function () {
    it('shows anyone in the project each family\'s unit and its place in the family\'s ranking', function () {
        // A and B both want unit 0 most; C wants unit 1: A gets its 1st, B its 2nd, C its 1st
        [$families, $units] = familiesRanking([[0, 1, 2], [0, 2, 1], [1, 0, 2]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();
        $member = $families[1]->members()->first();

        $page = $this->as($member, $this->project)->get(route('lottery.record', [$this->project, $run]))->assertOk();
        $places = collect($page->inertiaProps('families'))->pluck('place', 'id');

        expect($places->all())->toEqual([$families[0]->id => 1, $families[1]->id => 2, $families[2]->id => 1])
            ->and($page->inertiaProps('worst'))->toBe(2)
            ->and($page->inertiaProps('own.ranking'))->toBe([$units[0]->identifier, $units[2]->identifier, $units[1]->identifier])
            ->and($page->inertiaProps('fingerprint'))->toMatch('/^[0-9a-f]{64}$/');
    });

    it('keeps its fingerprint for as long as the record is untouched, and loses it if it is changed', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();
        $fingerprint = fn () => (new App\Services\Lottery\RunRecord($run->fresh()))->fingerprint();
        $before = $fingerprint();

        expect($fingerprint())->toBe($before);

        $audit = $run->audits()->where('type', 'project_execution')->sole();
        $audit->update(['audit' => [...$audit->audit, 'picks' => array_reverse($audit->audit['picks'], true)]]);

        expect($fingerprint())->not->toBe($before);
    });

    it('still shows after a family in it is archived or deleted, with the same fingerprint', function () {
        [$families, $units] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();
        $record = fn () => $this->as($this->admin, $this->project)->get(route('lottery.record', [$this->project, $run]))->assertOk();
        $before = $record()->inertiaProps();
        [$archived, $gone] = $families;
        $archived->delete();
        $gone->delete();

        $this->as($this->admin, $this->project)->delete(route('manage.archive.families.destroy', $gone->id))->assertSessionHasNoErrors();

        $after = $record()->inertiaProps();
        $shown = collect($after['families'])->keyBy('id');

        expect($after['fingerprint'])->toBe($before['fingerprint'])
            ->and($after['worst'])->toBe($before['worst'])
            ->and($after['average'])->toBe($before['average'])
            ->and($shown[$archived->id]['name'])->toBe($archived->name)
            ->and($shown[$gone->id]['name'])->toBe("Deleted family #{$gone->id}")
            ->and($shown[$gone->id]['unit'])->toEqual(collect($before['families'])->firstWhere('id', $gone->id)['unit']);
        $this->as($this->admin, $this->project)->get(route('lottery.download', [$this->project, $run]))->assertOk()->assertJsonPath('fingerprint', $before['fingerprint']);
    });

    it('shows a run recorded before the lottery spoke of participants, as it was written', function () {
        [$families, $units] = familiesRanking([[0, 1, 2], [0, 2, 1], [1, 0, 2]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();
        $member = $families[1]->members()->first();
        $record = fn () => $this->as($member, $this->project)->get(route('lottery.record', [$this->project, $run]))->assertOk()->inertiaProps();
        $before = $record();

        // The words it was written in then: families and units
        $init = $run->audits()->where('type', 'init')->sole();
        $written = $init->audit;
        foreach ($written['manifest']['data'] as $type => $group) {
            $written['manifest']['data'][$type] = ['families' => $group['participants'], 'units' => $group['items']];
        }
        $init->update(['audit' => $written]);

        $after = $record();

        expect($after['families'])->toEqual($before['families'])
            ->and($after['worst'])->toBe(2)
            ->and($after['own']['ranking'])->toBe([$units[0]->identifier, $units[2]->identifier, $units[1]->identifier]);
    });

    it('tells apart families of the same name', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        $families->each->update(['name' => 'García']);
        $names = $families->map(fn ($family) => 'García (' . $family->members()->value('firstname') . ')');
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();

        $shown = collect($this->as($this->admin, $this->project)->get(route('lottery.record', [$this->project, $run]))->inertiaProps('families'))->pluck('name', 'id');

        expect($shown[$families[0]->id])->toBe($names[0])
            ->and($shown[$families[1]->id])->toBe($names[1]);
    });

    it('lets admins download the whole record, and no one else', function () {
        [$families] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();

        $download = $this->as($this->admin, $this->project)->get(route('lottery.download', [$this->project, $run]))->assertOk();
        expect($download->json('trail.0.type'))->toBe('init')
            ->and($download->headers->get('Content-Disposition'))->toContain('attachment');

        expect($this->as($families[0]->members()->first(), $this->project)->get(route('lottery.download', [$this->project, $run])))->toBeDenied();
    });

    it('has no record for a run that decided nothing', function () {
        $run = aLotteryInProgress($this->project);

        $this->as($this->admin, $this->project)->get(route('lottery.record', [$this->project, $run]))->assertNotFound();
    });
});

describe('What a run decides from', function () {
    it('is what it recorded when it started, whatever the project holds by the time it is solved', function () {
        [$families, $units] = familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        Illuminate\Support\Facades\Queue::fake([RunLottery::class]);
        runLottery()->assertSessionHasNoErrors();
        $run = $this->project->lotteryRuns()->sole();

        // Rankings swapped after the run started, as a change slipping in would
        DB::table('unit_preferences')->where('family_id', $families[0]->id)->delete();
        DB::table('unit_preferences')->where('family_id', $families[1]->id)->delete();
        DB::table('unit_preferences')->insert([
            ['family_id' => $families[0]->id, 'unit_id' => $units[1]->id, 'rank' => 1],
            ['family_id' => $families[1]->id, 'unit_id' => $units[0]->id, 'rank' => 1],
        ]);

        app()->call([new RunLottery($run), 'handle']);

        $places = collect((new App\Services\Lottery\RunRecord($run->fresh()))->summary()['families'])->pluck('place', 'id');
        expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED)
            ->and(assignment($families))->toBe([$units[0]->id, $units[1]->id])
            ->and($places->all())->toEqual([$families[0]->id => 1, $families[1]->id => 1]);
    });

    it('fails a run that recorded nothing to decide from, deciding nothing', function () {
        familiesRanking([[0, 1], [1, 0]]);
        $run = aLotteryInProgress($this->project);

        app()->call([new RunLottery($run), 'handle']);

        expect($run->fresh()->status)->toBe(LotteryRunStatus::FAILED)
            ->and($this->project->units()->whereNotNull('family_id')->exists())->toBeFalse();
    });

    it('holds rankings still once it started: a ranking sent then is refused', function () {
        [$families, $units] = familiesRanking([[0, 1], [1, 0]]);
        aLotteryInProgress($this->project);

        $this->as($families[0]->members()->first(), $this->project)->put(route('lottery.preferences'), ['preferences' => [$units[1]->id]])
            ->assertSessionHas('error', __('lottery.locked'));

        expect(DB::table('unit_preferences')->where('family_id', $families[0]->id)->orderBy('rank')->pluck('unit_id')->all())
            ->toBe([$units[0]->id, $units[1]->id]);
    });
});

describe('Running it needs', function () {
    it('at least two families', function () {
        familiesRanking([[0]]);
        lotteryIsDue();

        runLottery()->assertSessionHas('error', __('lottery.cannot_run_too_few_families'));

        expect($this->project->lotteryRuns()->count())->toBe(0);
    });

    it('a date', function () {
        familiesRanking([[0, 1], [1, 0]]);
        $this->project->lottery->update(['start_date' => null]);

        runLottery()->assertSessionHas('error', __('lottery.cannot_run_not_scheduled'));

        expect($this->project->lotteryRuns()->count())->toBe(0);
    });

    it('counts a family waiting for someone invited, which receives a unit like any other', function () {
        $units = collect([aUnit($this->type), aUnit($this->type)]);
        $ranking = aFamily($this->project, $this->type);
        aMemberOf($ranking);
        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name' => 'Invited', 'unit_type_id' => $this->type->id, 'email' => 'invited@example.com',
        ])->assertSessionHasNoErrors();
        $invited = $this->project->families()->firstWhere('name', 'Invited');
        lotteryIsDue();

        expect($invited->memberships()->exists())->toBeFalse()
            ->and($invited->invitations()->exists())->toBeTrue();
        runLottery()->assertSessionHasNoErrors();

        expect(assignment([$ranking, $invited]))->toEqualCanonicalizing($units->pluck('id')->all());
    });
});

describe('Its date', function () {
    it('cannot change once a run is running or decided', function (string $status) {
        $run = aLotteryInProgress($this->project);
        $run->update(['status' => $status]);
        $date = $this->project->lottery->start_date;

        $this->as($this->admin, $this->project)->put(route('manage.lottery.schedule'), [
            'description' => 'Moved', 'start_date' => now()->addMonth()->format('Y-m-d H:i'),
        ])->assertSessionHas('error', __('lottery.locked'));

        expect($this->project->lottery->fresh())->start_date->toEqual($date)->description->not->toBe('Moved');
    })->with(['running', 'completed']);

    it('changes again once the run failed or was invalidated', function (string $status) {
        aLotteryInProgress($this->project)->update(['status' => $status]);

        $this->as($this->admin, $this->project)->put(route('manage.lottery.schedule'), [
            'description' => 'Moved', 'start_date' => now()->addMonth()->format('Y-m-d H:i'),
        ])->assertSessionHasNoErrors();

        expect($this->project->lottery->fresh()->description)->toBe('Moved');
    })->with(['failed', 'invalidated']);
});

describe('Invalidating a run', function () {
    $invalidate = fn ($test, $run, string $reason = 'A family was missing from the draw') => $test->as($test->admin, $test->project)
        ->post(route('manage.lottery.invalidate', $run), ['reason' => $reason]);

    it('is only for a completed run', function (string $status) use ($invalidate) {
        $run = aLotteryInProgress($this->project);
        $run->update(['status' => $status]);

        $invalidate($this, $run)->assertSessionHas('error', __('flash.changed_meanwhile'));
        $this->postJson(route('manage.lottery.invalidate', $run), ['reason' => 'A family was missing from the draw'])->assertConflict();

        expect($run->fresh()->status->value)->toBe($status)
            ->and($run->audits()->where('type', 'invalidate')->exists())->toBeFalse();
    })->with(['running', 'failed', 'invalidated']);

    it('happens once when sent twice at the same moment: one record, one notice', function () {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        // Both requests read it completed before either undid it
        [$first, $second] = [$this->project->lotteryRuns()->sole(), $this->project->lotteryRuns()->sole()];
        $lottery = app(App\Services\LotteryService::class);

        $lottery->invalidate($first, $this->admin, 'A family was missing from the draw');

        expect(fn () => $lottery->invalidate($second, $this->admin, 'A family was missing, again'))
            ->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect($first->audits()->where('type', 'invalidate')->count())->toBe(1)
            ->and(Notification::where('type', 'lottery_invalidated')->count())->toBe(1)
            ->and($first->fresh()->invalidation_reason)->toBe('A family was missing from the draw');
    });

    it('takes a reason of 10 to 1000 characters', function () use ($invalidate) {
        familiesRanking([[0, 1], [1, 0]]);
        lotteryIsDue();
        runLottery();
        $run = $this->project->lotteryRuns()->sole();

        $invalidate($this, $run, str_repeat('é', 9))->assertSessionHasErrors('reason');
        $invalidate($this, $run, str_repeat('é', 1001))->assertSessionHasErrors('reason');
        expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED);

        $invalidate($this, $run, str_repeat('é', 10))->assertSessionHasNoErrors();
        expect($run->fresh()->status)->toBe(LotteryRunStatus::INVALIDATED);

        runLottery();
        $invalidate($this, $this->project->lotteryRuns()->latest('id')->first(), str_repeat('😀', 1000))->assertSessionHasNoErrors();
        expect($this->project->lotteryRuns()->latest('id')->first()->invalidation_reason)->toBe(str_repeat('😀', 1000));
    });
});
