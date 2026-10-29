<?php

use App\Models\PlanItem;

/**
 * The plan: drawing it, and seeing it.
 */

/** The plan, switched to editing in place on the units page */
function editPlan($test): object
{
    return visit(route('units.index', $test->project))->click('Edit the plan')->assertPresent('[aria-label="Plan editor"]');
}

/** An item of the menu open on the plan */
function menuItem(string $text): string
{
    return "[role=\"menuitem\"]:has-text(\"{$text}\")";
}

/** A shape named where it stands: double-clicked, its name typed, Enter */
function nameShape(object $page, string $shape, string $name): object
{
    $page->script("() => document.querySelector('{$shape}').dispatchEvent(new MouseEvent('dblclick', { bubbles: true }))");

    return $page->fill('shape_name', $name)->keys('[name="shape_name"]', 'Enter');
}

beforeEach(function () {
    $this->project = aProject();
    $this->type = aUnitType($this->project);
    aUnit($this->type, ['identifier' => 'A-101']);
    $this->admin = anAdminOf($this->project);
});

it('adds a named green area that members then see', function () {
    $this->as($this->admin);

    $page = editPlan($this)->click('.plan-palette >> ' . button('Green area'));
    nameShape($page, '.plan-item[data-kind="green"] .plan-top', 'Central park')
        ->assertNotPresent('.plan-name-field')
        ->click('Done editing')
        ->assertSee('Edit the plan')
        ->assertNoSmoke();

    expect(PlanItem::where('type', 'green')->sole()->name)->toBe('Central park');

    $this->as(aMemberOf(aFamily($this->project, $this->type)));
    visit(route('units.index', $this->project))->assertSee('Central park');
});

it('moves a shape with the keyboard and undoes it', function () {
    $this->as($this->admin);
    $item = PlanItem::whereNotNull('unit_id')->sole();
    $before = $item->polygon;

    editPlan($this)
        ->click('[data-shape="A-101"] .plan-top')
        ->keys('[aria-label="Plan editor"]', 'ArrowRight')
        ->click('[aria-label="Undo"]')
        ->keys('[aria-label="Plan editor"]', 'ArrowDown')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect($item->fresh()->polygon[0])->toBe([$before[0][0], $before[0][1] + 5]);
});

it('puts units on other floors', function () {
    $this->as($this->admin);

    editPlan($this)
        ->rightClick('[data-shape="A-101"] .plan-top')
        ->click(menuItem('One floor up'))
        ->assertPresent('[role="tab"][aria-selected="true"]:has-text("Floor 1")')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::whereNotNull('unit_id')->sole()->floor)->toBe(1);

    $this->as(aMemberOf(aFamily($this->project, $this->type)));
    visit(route('units.index', $this->project))->assertSee('Floor 1');
});

it('redoes what was undone', function () {
    $this->as($this->admin);
    $item = PlanItem::whereNotNull('unit_id')->sole();
    $before = $item->polygon;

    editPlan($this)
        ->click('[data-shape="A-101"] .plan-top')
        ->keys('[aria-label="Plan editor"]', 'ArrowRight')
        ->click('[aria-label="Undo"]')
        ->click('[aria-label="Redo"]')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect($item->fresh()->polygon[0])->toBe([$before[0][0] + 5, $before[0][1]]);
});

it('duplicates a shape, and never a unit', function () {
    $this->as($this->admin);

    $page = editPlan($this)
        ->rightClick('[data-shape="A-101"] .plan-top')
        ->assertPresent(menuItem('Rotate'))
        ->assertNotPresent(menuItem('Duplicate'))
        ->keys('[aria-label="Plan editor"]', 'Escape')
        ->click('.plan-palette >> ' . button('Green area'));
    nameShape($page, '.plan-item[data-kind="green"] .plan-top', 'Park')
        ->rightClick('.plan-item[data-kind="green"] .plan-top')
        ->click(menuItem('Duplicate'))
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::where('type', 'green')->pluck('name')->all())->toBe(['Park', 'Park']);
});

it('removes a corner of the boundary', function () {
    $this->as($this->admin);
    $corners = count($this->project->plan->polygon);

    editPlan($this)
        ->click('Edit the boundary')
        ->rightClick('.plan-vertex >> nth=0')
        ->click(menuItem('Remove the corner'))
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect($this->project->plan->fresh()->polygon)->toHaveCount($corners - 1);
});

it('adds each kind from the palette in its starting shape, a street long and narrow', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('.plan-palette >> ' . button('Street'))
        ->click('.plan-palette >> ' . button('Entrance'))
        ->click('Done editing')
        ->assertSee('Edit the plan');

    $street = bounds(PlanItem::where('type', 'street')->sole()->polygon);
    expect($street['width'])->toBeGreaterThan($street['height'] * 4);
    expect(PlanItem::where('type', 'entrance')->sole()->polygon)->toHaveCount(3);
});

it('widens a shape by dragging one of its edges, and rotates it with R', function () {
    $this->as($this->admin);

    $page = editPlan($this)->click('.plan-palette >> ' . button('Street'));

    // The bottom edge, dragged 40 units down: both its corners go with it
    $page->script(<<<'JS'
        () => {
            const svg = document.querySelector('[aria-label="Plan editor"]');
            const edges = [...svg.querySelectorAll('.plan-edge')];
            const bottom = edges.reduce((low, edge) => (+edge.getAttribute('y1') > +low.getAttribute('y1') ? edge : low));
            const toScreen = (x, y) => new DOMPoint(x, y).matrixTransform(svg.getScreenCTM());
            const x = (+bottom.getAttribute('x1') + +bottom.getAttribute('x2')) / 2;
            const y = +bottom.getAttribute('y1');
            const from = toScreen(x, y);
            const to = toScreen(x, y + 40);
            const fire = (target, type, at) => target.dispatchEvent(new PointerEvent(type, { bubbles: true, clientX: at.x, clientY: at.y, pointerId: 1 }));
            fire(bottom, 'pointerdown', from);
            fire(svg, 'pointermove', to);
            fire(svg, 'pointerup', to);
        }
        JS);
    $page->keys('[aria-label="Plan editor"]', 'r')->click('Done editing')
        ->assertSee('Edit the plan');

    $polygon = PlanItem::where('type', 'street')->sole()->polygon;
    $box = bounds($polygon);
    // Taller by the drag, then a quarter turn of 15°: no longer square to the canvas
    expect(round($box['height']))->toBeGreaterThan(80);
    expect(abs($polygon[0][1] - $polygon[1][1]))->toBeGreaterThan(5);
});

/** A polygon's box: width and height */
function bounds(array $polygon): array
{
    $xs = array_column($polygon, 0);
    $ys = array_column($polygon, 1);

    return ['width' => max($xs) - min($xs), 'height' => max($ys) - min($ys)];
}

it('saves by itself a moment after a change', function () {
    $this->as($this->admin);

    editPlan($this)->click('.plan-palette >> ' . button('Green area'))->wait(1.5)->assertSee('Saved');

    expect(PlanItem::where('type', 'green')->exists())->toBeTrue();
});

it('still undoes after a reload', function () {
    $this->as($this->admin);

    $page = editPlan($this)->click('.plan-palette >> ' . button('Green area'))->wait(1.5)->assertSee('Saved');
    expect(PlanItem::where('type', 'green')->exists())->toBeTrue();

    // The page loaded anew before reaching for it
    $page->refresh()
        ->assertPresent('[aria-label="Project plan"]')
        ->click('Edit the plan')
        ->assertPresent('[aria-label="Plan editor"]')
        ->keys('[aria-label="Plan editor"]', 'Control+z')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::where('type', 'green')->exists())->toBeFalse();
});

it('keeps a shape brought to the front, and pastes copies', function () {
    $this->as($this->admin);

    $page = editPlan($this)
        ->click('.plan-palette >> ' . button('Other'))
        ->click('.plan-palette >> ' . button('Green area'))
        ->keys('[aria-label="Plan editor"]', ['Control+c'])
        ->keys('[aria-label="Plan editor"]', ['Control+v']);
    $page->script(<<<'JS'
            () => {
                const shape = document.querySelector('.plan-item[data-kind="other"] .plan-top');
                const box = shape.getBoundingClientRect();
                shape.dispatchEvent(new MouseEvent('contextmenu', { bubbles: true, clientX: box.x + 5, clientY: box.y + 5 }));
            }
            JS);
    $page->click('Bring to front')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect($this->project->plan->items()->whereNull('unit_id')->pluck('type')->all())->toBe(['green', 'green', 'other']);
});

it('adds a unit from the palette: its details first, then it stands where it was drawn', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('Add a floor')
        ->click('.plan-palette >> ' . button('Unit'))
        ->fill(inDialogField('identifier'), 'A-201')
        ->click(inDialog('Add the unit'))
        ->assertPresent('[data-shape="A-201"]')
        ->assertSee('A-201')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    $item = PlanItem::whereHas('unit', fn ($unit) => $unit->where('identifier', 'A-201'))->sole();
    expect($item->floor)->toBe(1)
        ->and($item->unit->unit_type_id)->toBe($this->type->id)
        ->and(PlanItem::whereNotNull('unit_id')->count())->toBe(2);
});

it('adds no unit when its details are left', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('.plan-palette >> ' . button('Unit'))
        ->assertPresent(inDialogField('identifier'))
        ->click(inDialog('Cancel'))
        ->assertNotPresent('[role="dialog"]')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::whereNotNull('unit_id')->count())->toBe(1);
});

it('puts a unit added elsewhere on the plan from where it waits, and only then shows it', function () {
    // Added once the site is drawn: it waits
    $this->project->plan->items()->create(['type' => 'green', 'floor' => 0, 'polygon' => [[0, 0], [80, 0], [80, 80], [0, 80]]]);
    aUnit($this->type, ['identifier' => 'W-1']);

    $this->as(aMemberOf(aFamily($this->project, $this->type)));
    visit(route('units.index', $this->project))->assertDontSeeIn('.plan-canvas', 'W-1');

    $this->as($this->admin);
    editPlan($this)
        ->click('[aria-label="Place W-1 on the plan"]')
        ->assertPresent('[data-shape="W-1"]')
        ->assertNotPresent('.plan-waiting')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::whereHas('unit', fn ($unit) => $unit->where('identifier', 'W-1'))->sole()->placed)->toBeTrue();
});

it('keeps units over every other shape, whatever is brought to the front', function () {
    $this->as($this->admin);

    $page = editPlan($this)->click('.plan-palette >> ' . button('Green area'));
    $page->script(<<<'JS'
            () => {
                const shape = document.querySelector('.plan-item[data-kind="green"] .plan-top');
                const box = shape.getBoundingClientRect();
                shape.dispatchEvent(new MouseEvent('contextmenu', { bubbles: true, clientX: box.x + 5, clientY: box.y + 5 }));
            }
            JS);
    $page->click('Bring to front');

    $drawn = $page->script("() => [...document.querySelectorAll('[aria-label=\"Plan editor\"] > .plan-shape')].map((shape) => shape.querySelector('.plan-item').dataset.kind)");
    expect($drawn)->toBe(['green', 'unit']);
});

it('makes the canvas taller from its bottom edge', function () {
    $this->as($this->admin);
    $height = $this->project->plan->height;

    $page = editPlan($this);
    $page->script(<<<'JS'
        () => {
            const grip = document.querySelector('.plan-grip');
            const box = grip.getBoundingClientRect();
            const fire = (type, y) => grip.dispatchEvent(new PointerEvent(type, { bubbles: true, clientX: box.x + box.width / 2, clientY: y, pointerId: 1 }));
            fire('pointerdown', box.y);
            fire('pointermove', box.y + 120);
            fire('pointerup', box.y + 120);
        }
        JS);
    $page->click('Done editing')->assertSee('Edit the plan');

    expect($this->project->plan->fresh()->height)->toBeGreaterThan($height);
});

it('zooms the plan people read, and says how to zoom with the wheel', function () {
    $this->as(aMemberOf(aFamily($this->project, $this->type)));
    $page = visit(route('units.index', $this->project));
    $whole = $page->script("() => document.querySelector('[aria-label=\"Project plan\"]').getAttribute('viewBox')");

    $page->click('[aria-label="Zoom in"]');
    $zoomed = $page->script("() => document.querySelector('[aria-label=\"Project plan\"]').getAttribute('viewBox')");
    expect(explode(' ', $zoomed)[2])->toBeLessThan(explode(' ', $whole)[2]);

    $page->script("() => document.querySelector('[aria-label=\"Project plan\"]').dispatchEvent(new WheelEvent('wheel', { bubbles: true, cancelable: true, deltaY: 100 }))");
    $page->assertSee('scroll to zoom the plan')
        ->click('[aria-label="The whole plan"]')
        ->assertNoSmoke();
    expect($page->script("() => document.querySelector('[aria-label=\"Project plan\"]').getAttribute('viewBox')"))->toBe($whole);
});

it('shows the plan over the whole window, and back with Escape', function () {
    $this->as(aMemberOf(aFamily($this->project, $this->type)));

    visit(route('units.index', $this->project))
        ->click('[aria-label="Full screen"]')
        ->assertPresent('.plan-panel[data-full]')
        ->keys('.plan-panel', 'Escape')
        ->assertNotPresent('.plan-panel[data-full]')
        ->assertNoSmoke();
});

it('names a new shape after its kind, a name that may be cleared', function () {
    $this->as($this->admin);

    $page = editPlan($this)
        ->click('.plan-palette >> ' . button('Green area'))
        ->assertSeeIn('[aria-label="Plan editor"]', 'Green area');
    nameShape($page, '.plan-item[data-kind="green"] .plan-top', '')
        ->assertDontSeeIn('[aria-label="Plan editor"] .plan-label', 'Green area')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::where('type', 'green')->sole()->name)->toBeNull();
});

it('tells a second admin who is drawing the plan, and leaves them reading it', function () {
    $other = anAdminOf($this->project);
    $other->update(['firstname' => 'Ana', 'lastname' => 'Pérez']);
    app(App\Services\Plan\EditingLock::class)->take($this->project->plan, $other);
    $this->as($this->admin);

    visit(route('units.index', $this->project))
        ->click('Edit the plan')
        ->assertSee('Ana Pérez is drawing the plan right now')
        ->assertNotPresent('[aria-label="Plan editor"]')
        ->assertPresent('[aria-label="Project plan"]');
});
