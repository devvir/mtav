<?php

use App\Enums\LotteryRunStatus;
use App\Services\Queue\Workers;
use Illuminate\Queue\Events\Looping;

/**
 * The lottery runs in the background: while nothing works the queue, it is held, and
 * everyone sees why on the lottery page, before anyone tries.
 */

beforeEach(function () {
    config(['queue.default' => 'database']);
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $type = aUnitType($this->project);
    collect([1, 2])->each(fn () => aUnit($type));
    collect([1, 2])->each(fn () => aMemberOf(aFamily($this->project, $type)));
    $this->project->lottery->update(['start_date' => now()->subMinute()]);
});

it('holds the lottery, and says so, while nothing works the queue', function () {
    expect($this->as($this->admin, $this->project)->get(route('lottery.show'))->inertiaProps('runnable'))->toBeFalse();

    $this->as($this->admin, $this->project)->post(route('manage.lottery.run'))
        ->assertSessionHas('error', __('lottery.cannot_run_no_worker'));
    expect($this->project->lotteryRuns()->count())->toBe(0);
});

it('takes no worker of another queue for one of the lottery\'s', function () {
    event(new Looping('database', 'realtime'));
    event(new Looping('database', 'assistant'));

    expect(Workers::running())->toBeFalse();

    event(new Looping('database', 'realtime,assistant,default'));

    expect(Workers::running())->toBeTrue();
});

it('never stops a worker by answering it as it loops', function () {
    expect(app('events')->until(new Looping('database', 'realtime')))->not->toBeFalse()
        ->and(app('events')->until(new Looping('database', 'default')))->not->toBeFalse();
});

it('runs it once a worker is seen working the queue', function () {
    event(new Looping('database', 'default'));

    expect($this->as($this->admin, $this->project)->get(route('lottery.show'))->inertiaProps('runnable'))->toBeTrue();
    $this->as($this->admin, $this->project)->post(route('manage.lottery.run'))->assertSessionHasNoErrors();
    expect($this->project->lotteryRuns()->sole()->status)->toBe(LotteryRunStatus::RUNNING);
});

it('takes a worker unseen for a while for stopped', function () {
    Workers::mark();
    $this->travel(3)->minutes();

    expect(Workers::running())->toBeFalse();
});
