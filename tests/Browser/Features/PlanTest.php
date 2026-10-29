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

beforeEach(function () {
    $this->project = aProject();
    $this->type = aUnitType($this->project);
    aUnit($this->type, ['identifier' => 'A-101']);
    $this->admin = anAdminOf($this->project);
});

it('adds a named green area that members then see', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('.plan-palette >> ' . button('Green area'))
        ->fill('shape_name', 'Central park')
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
        ->click('Add a floor')
        ->click('Ground floor')
        ->click('[data-shape="A-101"] .plan-top')
        ->select('shape_floor', '1')
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

    editPlan($this)
        ->click('[data-shape="A-101"] .plan-top')
        ->assertNotPresent(button('Duplicate'))
        ->click('.plan-palette >> ' . button('Green area'))
        ->fill('shape_name', 'Park')
        ->click('Duplicate')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect(PlanItem::where('type', 'green')->pluck('name')->all())->toBe(['Park', 'Park']);
});

it('removes a corner of the boundary', function () {
    $this->as($this->admin);
    $corners = count($this->project->plan->polygon);

    editPlan($this)
        ->click('Edit the boundary')
        ->click('.plan-vertex >> nth=0')
        ->click('Remove the corner')
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

it('widens a shape by dragging one of its edges, and turns it with R', function () {
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

    $page->refresh()
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
        ->click('.plan-palette >> ' . button('Building'))
        ->click('.plan-palette >> ' . button('Green area'))
        ->keys('[aria-label="Plan editor"]', ['Control+c'])
        ->keys('[aria-label="Plan editor"]', ['Control+v']);
    $page->script(<<<'JS'
            () => {
                const shape = document.querySelector('.plan-item[data-kind="building"] .plan-top');
                const box = shape.getBoundingClientRect();
                shape.dispatchEvent(new MouseEvent('contextmenu', { bubbles: true, clientX: box.x + 5, clientY: box.y + 5 }));
            }
            JS);
    $page->click('Bring to front')
        ->click('Done editing')
        ->assertSee('Edit the plan');

    expect($this->project->plan->items()->whereNull('unit_id')->pluck('type')->all())->toBe(['green', 'green', 'building']);
});
