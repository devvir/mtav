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

it('moves a unit down with its button', function () {
    $first = app(PreferencesService::class)->ranking($this->family)[0];
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->click("[aria-label=\"Move {$first->identifier} down\"]")
        ->assertSeeIn('ol li:last-child', $first->identifier)
        ->wait(2);

    expect(app(PreferencesService::class)->ranking($this->family)[1]->id)->toBe($first->id);
});

it('reorders the ranking with the keyboard', function () {
    $first = app(PreferencesService::class)->ranking($this->family)[0];
    $this->as($this->member);

    // Space picks the unit up from its grip, the arrows move it, Space drops it
    $grip = "[aria-label=\"Move {$first->identifier}, number 1\"]";
    visit(route('lottery.show', $this->project))
        ->keys($grip, 'Space')
        ->assertAttribute($grip, 'aria-pressed', 'true')
        // The arrows are listened to a moment after the pick-up; with two units, a second
        // press cannot move it past the end
        ->wait(0.5)
        ->keys($grip, ['ArrowDown', 'ArrowDown'])
        // Dropped once the move registered (announced to screen readers)
        ->assertScript(
            "[...document.querySelectorAll('[id^=DndLiveRegion]')].some((region) => region.textContent.includes('goes to number 2'))",
            true,
        )
        ->keys($grip, 'Space')
        ->assertSeeIn('ol li:last-child', $first->identifier)
        ->wait(2);

    expect(app(PreferencesService::class)->ranking($this->family)[1]->id)->toBe($first->id);
});

it('reorders the ranking by dragging', function () {
    [$first, $second] = app(PreferencesService::class)->ranking($this->family)->all();
    $this->as($this->member);

    // A hand's drag: press on the row, move a little at a time, release over the other row
    $page = visit(route('lottery.show', $this->project))->assertSee($second->identifier);
    $page->script(<<<'JS'
        async () => {
            const [top, bottom] = document.querySelectorAll('ol.ranking > li');
            const at = (el) => { const r = el.getBoundingClientRect(); return [r.x + r.width / 2, r.y + r.height / 2]; };
            const [x, y] = at(bottom);
            const [, toY] = at(top);
            const fire = (target, type, cx, cy) => target.dispatchEvent(new MouseEvent(type, { bubbles: true, clientX: cx, clientY: cy, button: 0, buttons: 1 }));
            const pause = () => new Promise((resolve) => setTimeout(resolve, 20));
            fire(bottom, 'mousedown', x, y);
            for (let step = 1; step <= 12; step++) { fire(document, 'mousemove', x, y + ((toY - y) * step) / 12); await pause(); }
            fire(document, 'mouseup', x, toY);
        }
        JS);
    $page->assertSeeIn('ol li:first-child', $second->identifier)->wait(2);

    expect(app(PreferencesService::class)->ranking($this->family)[0]->id)->toBe($second->id);
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
