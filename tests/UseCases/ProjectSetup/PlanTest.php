<?php

/**
 * The plan: its admins draw the land and what is on it, of every kind people choose by
 * (buildings, streets, green areas, common spaces, parking, playgrounds, entrances).
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
});

function drawPlan($test, $by, array $items)
{
    $square = [[10, 10], [110, 10], [110, 90], [10, 90]];

    return $test->as($by, $test->project)->put(route('manage.plan.update'), [
        'width'   => 800,
        'height'  => 600,
        'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
        'items'   => array_map(fn ($type) => ['type' => $type, 'floor' => 0, 'name' => ucfirst($type), 'polygon' => $square], $items),
    ]);
}

it('draws every kind of place people choose by', function () {
    $kinds = ['building', 'street', 'green', 'common', 'parking', 'playground', 'entrance', 'other'];

    drawPlan($this, $this->admin, $kinds)->assertSessionHasNoErrors();

    expect($this->project->plan->items()->pluck('type')->sort()->values()->all())->toBe(collect($kinds)->sort()->values()->all());
});

it('refuses a kind of place it does not know', function () {
    drawPlan($this, $this->admin, ['swimming pool'])->assertSessionHasErrors('items.0.type');

    expect($this->project->plan->items()->count())->toBe(0);
});

it('lets only admins draw it', function () {
    $member = aMemberOf(aFamily($this->project));

    expect(drawPlan($this, $member, ['green']))->toBeDenied();
    expect($this->project->plan->items()->count())->toBe(0);
});
