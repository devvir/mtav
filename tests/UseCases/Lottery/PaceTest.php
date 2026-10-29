<?php

use App\Enums\LotteryRunStatus;
use App\Jobs\PublishLotteryRun;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Queue;

/**
 * The lottery solves in an instant; people see it work for the time superadmins set,
 * step by step, and only then its result, everywhere at once.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
    aUnit($this->type);
    aUnit($this->type);
    $this->family = aFamily($this->project, $this->type);
    $this->member = aMemberOf($this->family);
    aMemberOf(aFamily($this->project, $this->type));
    $this->project->lottery->update(['start_date' => now()->subMinute()]);
});

it('shows the lottery running for the time set, the result held until then', function () {
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 30);
    Queue::fake([PublishLotteryRun::class]);

    $this->as($this->admin, $this->project)->post(route('manage.lottery.run'))->assertSessionHasNoErrors();

    $run = $this->project->lotteryRuns()->sole();
    $page = $this->as($this->member, $this->project)->get(route('lottery.show'));
    expect($run->status)->toBe(LotteryRunStatus::RUNNING)
        ->and($this->family->fresh()->unit)->toBeNull()
        ->and($page->inertiaProps('assignment'))->toBeNull()
        ->and($page->inertiaProps('pace.reveal_at'))->not->toBeNull();
    Queue::assertPushed(PublishLotteryRun::class, fn ($job) => $job->delay->equalTo($run->reveal_at));

    (new PublishLotteryRun($run))->handle(app(App\Services\LotteryService::class));

    expect($run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED)
        ->and($this->family->fresh()->unit)->not->toBeNull();
});

it('shows the result at once when no time is set', function () {
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);
    Queue::fake([PublishLotteryRun::class]);

    $this->as($this->admin, $this->project)->post(route('manage.lottery.run'));

    expect($this->project->lotteryRuns()->sole()->status)->toBe(LotteryRunStatus::COMPLETED);
    Queue::assertNotPushed(PublishLotteryRun::class);
});

it('fails a run whose held result cannot be written, so the lottery can run again', function () {
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 30);
    Queue::fake([PublishLotteryRun::class]);
    $this->as($this->admin, $this->project)->post(route('manage.lottery.run'));
    $run = $this->project->lotteryRuns()->sole();

    (new PublishLotteryRun($run))->failed(new RuntimeException('The database went away'));

    expect($run->fresh()->status)->toBe(LotteryRunStatus::FAILED);
});

it('lets only superadmins set how long it takes, within bounds', function () {
    $this->as($this->admin)->put(route('admin.settings.update'), ['lottery_seconds' => 5])->assertNotFound();
    $this->as(aSuperadmin())->put(route('admin.settings.update'), ['lottery_seconds' => 9999])
        ->assertSessionHasErrors('lottery_seconds');
    $this->as(aSuperadmin())->put(route('admin.settings.update'), ['lottery_seconds' => 45])
        ->assertSessionHasNoErrors();

    expect(PlatformSetting::value(PlatformSetting::LOTTERY_SECONDS))->toBe(45);
});
