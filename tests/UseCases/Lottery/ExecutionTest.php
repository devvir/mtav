<?php

use App\Jobs\RunLottery;
use App\Enums\LotteryRunStatus;
use App\Models\Event;
use App\Models\Notification;
use App\Services\Lottery\Contracts\SolverInterface;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;
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
            ->and($run->failure)->toBe(__('lottery.execution_failed'))
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

/** A solver that breaks, as an unreachable or crashing one would */
class BrokenSolver implements SolverInterface
{
    public function execute(LotteryManifest $manifest, LotterySpec $spec): ExecutionResult
    {
        throw new RuntimeException('The solver crashed');
    }
}
