<?php

use App\Models\LotteryRun;
use App\Services\Lottery\PreferencesService;

/**
 * The lottery: rankings, locking, confirmations, invalidation.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
    $this->units = collect(['A-101', 'A-102'])->map(fn ($id) => aUnit($this->type, ['identifier' => $id]));
    $this->family = aFamily($this->project, $this->type, ['name' => 'García']);
    $this->member = aMemberOf($this->family);
    aMemberOf(aFamily($this->project, $this->type, ['name' => 'Pérez']));
});

function lotteryDue($test): void
{
    $test->project->lottery->update(['start_date' => now()->subMinute()]);
}

function runFromUi($test)
{
    return visit(route('manage.lottery.show', $test->project))
        ->click('Run the lottery')
        ->fill('[role="dialog"] input', 'RUN')
        ->keys('[role="dialog"] input', 'Enter');
}

it('keeps a family\'s ranking after leaving the page', function () {
    $second = app(PreferencesService::class)->ranking($this->family)[1];
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->click("[aria-label=\"Move {$second->identifier} up\"]")
        ->wait(2);

    visit(route('lottery.show', $this->project))
        ->assertSeeIn('ol li:first-child', $second->identifier);
});

it('locks the ranking while the lottery runs', function () {
    aLotteryInProgress($this->project);
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->assertSee('The lottery is running.')
        ->assertDontSee('Move A-101 up')
        ->assertNoSmoke();
});

it('cannot run before its date', function () {
    $this->project->lottery->update(['start_date' => now()->addWeek()]);
    $this->as($this->admin);

    $page = visit(route('manage.lottery.show', $this->project))->assertSee('It can run once its date arrives.');

    expect($page->script('[...document.querySelectorAll("button")].find((b) => b.textContent.includes("Run the lottery")).disabled'))->toBeTrue();
});

it('asks to confirm when units and families do not match', function () {
    aUnit($this->type, ['identifier' => 'A-103']);
    lotteryDue($this);
    $this->as($this->admin);

    runFromUi($this)
        ->assertSee('The number of units and families differ')
        ->click(inDialog('Run anyway'))
        ->assertSee('The lottery is done: every family has its unit.');
});

it('invalidates a run with a reason everyone reads, and runs again', function () {
    lotteryDue($this);
    $this->as($this->admin);

    $page = runFromUi($this)
        ->assertSee('The lottery is done: every family has its unit.')
        ->click('Invalidate')
        ->fill('[role="dialog"] textarea', 'too short');

    expect($page->script('document.querySelector(\'[role="dialog"] button[type="submit"]\').disabled'))->toBeTrue();

    $page->fill('[role="dialog"] textarea', 'The Pérez family was registered with the wrong type')
        ->click(inDialog('Invalidate'))
        ->assertSee('The lottery was invalidated.');

    expect($this->family->fresh()->unit)->toBeNull();

    $this->as($this->member);
    visit(route('lottery.show', $this->project))
        ->assertSee('A lottery run was invalidated')
        ->assertSee('The Pérez family was registered with the wrong type');

    $this->as($this->admin);
    runFromUi($this)->assertSee('The lottery is done: every family has its unit.');

    expect(LotteryRun::pluck('status')->map->value->sort()->values()->all())->toBe(['completed', 'invalidated']);
});

it('shows every family which unit it received', function () {
    lotteryDue($this);
    $this->as($this->admin);
    runFromUi($this)->assertSee('The lottery is done');

    $this->as($this->member);
    visit(route('projects.show', $this->project))
        ->assertSee('Your family received')
        ->assertSee($this->family->fresh()->unit->identifier);
});
