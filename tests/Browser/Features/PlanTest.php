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
        ->select('[aria-label="Shape to add"]', 'green')
        ->click('Add')
        ->fill('shape_name', 'Central park')
        ->click('Save the plan')
        ->assertSee('The plan was saved.')
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
        ->click('Save the plan')
        ->assertSee('The plan was saved.');

    expect($item->fresh()->polygon[0])->toBe([$before[0][0], $before[0][1] + 5]);
});

it('puts units on other floors', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('Add a floor')
        ->click('Ground floor')
        ->click('[data-shape="A-101"] .plan-top')
        ->select('shape_floor', '1')
        ->click('Save the plan')
        ->assertSee('The plan was saved.');

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
        ->click('Save the plan')
        ->assertSee('The plan was saved.');

    expect($item->fresh()->polygon[0])->toBe([$before[0][0] + 5, $before[0][1]]);
});

it('duplicates a shape, and never a unit', function () {
    $this->as($this->admin);

    editPlan($this)
        ->click('[data-shape="A-101"] .plan-top')
        ->assertNotPresent(button('Duplicate'))
        ->select('[aria-label="Shape to add"]', 'green')
        ->click('Add')
        ->fill('shape_name', 'Park')
        ->click('Duplicate')
        ->click('Save the plan')
        ->assertSee('The plan was saved.');

    expect(PlanItem::where('type', 'green')->pluck('name')->all())->toBe(['Park', 'Park']);
});

it('removes a corner of the boundary', function () {
    $this->as($this->admin);
    $corners = count($this->project->plan->polygon);

    editPlan($this)
        ->click('Edit the boundary')
        ->click('.plan-vertex >> nth=0')
        ->click('Remove the corner')
        ->click('Save the plan')
        ->assertSee('The plan was saved.');

    expect($this->project->plan->fresh()->polygon)->toHaveCount($corners - 1);
});
