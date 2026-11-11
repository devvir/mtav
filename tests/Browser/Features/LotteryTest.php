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
    return visit(route('lottery.show', $test->project))
        ->click('Run the lottery')
        ->fill('[role="dialog"] input', 'RUN')
        ->keys('[role="dialog"] input', 'Enter');
}

it('keeps a family\'s ranking after leaving the page', function () {
    $second = app(PreferencesService::class)->ranking($this->family)[1];
    $this->as($this->member);

    $page = visit(route('lottery.show', $this->project))
        ->click("[aria-label=\"Move {$second->identifier} up\"]");
    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[0]->id)->toBe($second->id));

    visit(route('lottery.show', $this->project))
        ->assertSeeIn('ol li:first-child', $second->identifier);
});

it('saves a move made just before going to another page, and stays there', function () {
    $second = app(PreferencesService::class)->ranking($this->family)[1];
    $this->as($this->member);

    $page = watchRequests(visit(route('lottery.show', $this->project)))
        ->click("[aria-label=\"Move {$second->identifier} up\"]")
        ->click('.shell-sidebar >> a:has-text("Units")')
        ->assertPathIs(route('units.index', $this->project, absolute: false));

    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[0]->identifier)->toBe($second->identifier));
    // The save's answer came after the visit: it never brings the lottery back
    answered($page)->assertPathIs(route('units.index', $this->project, absolute: false))->assertDontSee('Your preferences');
});

it('says so when there is nothing of the family\'s type to rank yet', function () {
    $empty = aUnitType($this->project, ['name' => 'Loft']);
    $this->as(aMemberOf(aFamily($this->project, $empty, ['name' => 'Ruiz'])));

    visit(route('lottery.show', $this->project))
        ->assertSeeIn('[data-tour="ranking.list"]', 'No Loft units yet')
        ->assertNoSmoke();
});

it('says, in a status screen readers announce, that a ranking is being saved and is saved', function () {
    $second = app(PreferencesService::class)->ranking($this->family)[1];
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->assertPresent('[data-tour="ranking.list"] [role="status"]')
        ->click("[aria-label=\"Move {$second->identifier} up\"]")
        ->assertSeeIn('[data-tour="ranking.list"] [role="status"]', 'Saving')
        ->assertSeeIn('[data-tour="ranking.list"] [role="status"]', 'Saved');
});

it('moves a unit down with its button', function () {
    $first = app(PreferencesService::class)->ranking($this->family)[0];
    $this->as($this->member);

    $page = visit(route('lottery.show', $this->project))
        ->click("[aria-label=\"Move {$first->identifier} down\"]")
        ->assertSeeIn('ol li:last-child', $first->identifier);

    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[1]->id)->toBe($first->id));
});

it('reorders the ranking with the keyboard', function () {
    $first = app(PreferencesService::class)->ranking($this->family)[0];
    $this->as($this->member);

    // Space picks the unit up from its grip, the arrows move it, Space drops it
    $grip = "[aria-label=\"Move {$first->identifier}, number 1\"]";
    $page = visit(route('lottery.show', $this->project))
        ->keys($grip, 'Space')
        // The arrows are listened to a moment after the pick-up; with two units, a second
        // press cannot move it past the end
        ->assertAttribute($grip, 'aria-pressed', 'true');
    settled($page)
        ->keys($grip, ['ArrowDown', 'ArrowDown'])
        // Dropped once the move registered (announced to screen readers)
        ->assertScript(
            "[...document.querySelectorAll('[id^=DndLiveRegion]')].some((region) => region.textContent.includes('goes to number 2'))",
            true,
        )
        ->keys($grip, 'Space')
        ->assertSeeIn('ol li:last-child', $first->identifier);

    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[1]->id)->toBe($first->id));
});

it('reorders the ranking by dragging', function () {
    [$first, $second] = app(PreferencesService::class)->ranking($this->family)->all();
    $this->as($this->member);

    // A hand's drag: press on the row, move a little at a time, release over the other row
    $page = visit(route('lottery.show', $this->project))->assertSee($second->identifier);
    $page->script(<<<'JS'
        async () => {
            // The plugin may run a script again after a slow first try: one drag, not two
            if (window.dragged) return;
            window.dragged = true;
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
    $page->assertSeeIn('ol li:first-child', $second->identifier);

    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[0]->id)->toBe($second->id));
});

it('locks the ranking while the lottery runs', function () {
    aLotteryInProgress($this->project);
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->assertSee('The lottery is running')
        ->assertSee("Reading every family's preferences")
        ->assertDontSee('Move A-101 up')
        ->assertNoSmoke();
});

it('cannot run before its date', function () {
    $this->project->lottery->update(['start_date' => now()->addWeek()]);
    $this->as($this->admin);

    $page = visit(route('lottery.show', $this->project))->assertSee('It can run once its date arrives.');

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

it('does not ask again, on going back, what the server asked before running', function () {
    aUnit($this->type, ['identifier' => 'A-103']);
    lotteryDue($this);
    $this->as($this->admin);

    $page = runFromUi($this)
        ->assertSee('The number of units and families differ')
        ->click(inDialog('Cancel'))
        ->assertMissing('[role="dialog"]')
        ->click('.shell-sidebar >> a:has-text("Home")')
        ->assertPathIs("/projects/{$this->project->id}");

    $page->script('() => history.back()');

    settled($page->assertPathIs("/projects/{$this->project->id}/lottery")->assertSeeIn('.ds-page-title', 'Lottery'))->assertMissing('[role="dialog"]');
});

it('keeps the focus on the arrow that took a unit to the top', function () {
    $this->as($this->member);

    $page = visit(route('lottery.show', $this->project));
    $page->keys('[aria-label="Move A-102 up"]', 'Enter');
    $page->assertPresent('[aria-label="Move A-102 up"][aria-disabled="true"]');

    expect($page->script('() => document.activeElement.getAttribute("aria-label")'))->toBe('Move A-102 up');
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
        ->assertSee('invalidated a lottery run');

    expect($this->family->fresh()->unit)->toBeNull();

    $this->as($this->member);
    visit(route('lottery.show', $this->project))
        ->assertSee('invalidated a lottery run')
        ->assertSee('The Pérez family was registered with the wrong type');

    $this->as($this->admin);
    runFromUi($this)->assertSee('The lottery is done: every family has its unit.');

    expect(LotteryRun::pluck('status')->map->value->sort()->values()->all())->toBe(['completed', 'invalidated']);
});

it('reads the date and description of a lottery that ran as text, never as a greyed-out form', function () {
    $this->project->lottery->update(['description' => 'In the common hall, streamed for whoever cannot come.']);
    lotteryDue($this);
    $this->as($this->admin);

    runFromUi($this)
        ->assertSee('The lottery is done')
        ->assertSee('In the common hall, streamed for whoever cannot come.')
        ->assertSee('They stay as they were once the lottery ran.')
        ->assertMissing('[data-tour="lottery.schedule"] textarea')
        ->assertNoSmoke();
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

/** A unit of the plan, dragged by the pointer onto a place of the ranking under it */
function dragFromPlan(object $page, string $unit, int $place): void
{
    $page->script(<<<JS
        () => {
            // The plugin may run a script again after a slow first try: one drag, not two
            if (window.dragged) return;
            window.dragged = true;
            const unit = [...document.querySelectorAll('[aria-label="Project plan"] [data-unit]')]
                .find((shape) => shape.querySelector('.plan-label')?.textContent === '{$unit}');
            const slot = document.querySelector('[data-slot="{$place}"]');
            // Where a person would drag: the place in view, the plan above it
            slot.scrollIntoView({ block: 'center' });
            const from = unit.getBoundingClientRect();
            const to = slot.getBoundingClientRect();
            const at = (box) => ({ clientX: box.x + box.width / 2, clientY: box.y + box.height / 2 });
            const fire = (target, type, box) => target.dispatchEvent(new PointerEvent(type, { bubbles: true, button: 0, pointerId: 1, pointerType: 'mouse', ...at(box) }));
            fire(unit.querySelector('.plan-top'), 'pointerdown', from);
            fire(window, 'pointermove', { x: to.x - 20, y: to.y, width: to.width, height: to.height });
            fire(window, 'pointermove', to);
            fire(window, 'pointerup', to);
        }
        JS);
}

it('ranks a unit by dragging it from the plan onto a place under it', function () {
    $last = app(PreferencesService::class)->ranking($this->family)[1];
    $this->as($this->member);

    $page = visit(route('lottery.show', $this->project))->assertPresent('[data-slot="0"]');
    dragFromPlan($page, $last->identifier, 0);
    $page->assertSeeIn('[data-slot="0"]', $last->identifier)
        ->assertSeeIn('ol li:first-child', $last->identifier);

    eventually($page, fn () => expect(app(PreferencesService::class)->ranking($this->family->fresh())[0]->id)->toBe($last->id));
});

it('lights up in the ranking a unit clicked on the plan', function () {
    $this->as($this->member);

    visit(route('lottery.show', $this->project))
        ->assertPresent('[aria-label="Project plan"] [data-unit]')
        ->click('[aria-label="Project plan"] [data-press] .plan-top >> nth=0')
        ->assertPresent('.ranking-item[data-flash]');
});

it('shows an admin outside every family the lottery tools, and nothing about ranking', function () {
    $this->as($this->admin);

    visit(route('lottery.show', $this->project))
        ->assertSee('Run the lottery')
        ->assertDontSee('Your preferences')
        ->assertDontSee('Families rank units')
        ->assertNoSmoke();
});

it('lets a family check the result: each family\'s place, its own ranking, the fingerprint', function () {
    lotteryDue($this);
    $this->as($this->admin);
    runFromUi($this)->assertSee('The lottery is done');

    $this->as($this->member);
    visit(route('lottery.show', $this->project))
        ->click('Check the result')
        ->assertSee('The lottery’s record')
        ->assertSee('García')
        ->assertSee('Pérez')
        ->assertSee('Your family’s ranking, as recorded')
        ->assertSee('Fingerprint')
        ->assertDontSee('Download the full record')
        ->assertNoSmoke();

    // The full record is the admins': theirs to download, refused to a member who asks for its address
    $download = route('lottery.download', [$this->project, LotteryRun::latest('id')->first()]);
    $fetch = fn ($page) => $page->script(<<<JS
        async () => {
            const response = await fetch('{$download}');
            const body = response.ok ? await response.json() : null;
            return { status: response.status, type: response.headers.get('content-type'), keys: body ? Object.keys(body) : [] };
        }
        JS);
    expect($fetch(visit(route('lottery.show', $this->project)))['status'])->toBe(403);

    $this->as($this->admin);
    $page = visit(route('lottery.show', $this->project))->click('Check the result')->assertSee('Download the full record');
    $answer = $fetch($page);
    expect($answer['status'])->toBe(200)
        ->and($answer['type'])->toContain('json')
        ->and($answer['keys'])->not->toBeEmpty();
});
