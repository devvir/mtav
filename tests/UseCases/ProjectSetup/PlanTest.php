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

/** The plan saved as the editor sends it: these shapes, as given */
function savePlan($test, array $items)
{
    return $test->as($test->admin, $test->project)->putJson(route('manage.plan.update'), [
        'width'   => 800,
        'height'  => 600,
        'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
        'items'   => $items,
    ]);
}

it('saves a shape brought back after its removal was saved, as a new one', function () {
    $square = [[10, 10], [60, 10], [60, 60], [10, 60]];
    $green = $this->project->plan->items()->create(['type' => 'green', 'floor' => 0, 'polygon' => $square]);
    $id = $green->id;
    $green->delete();

    $ids = savePlan($this, [['id' => $id, 'key' => "item-{$id}", 'type' => 'green', 'floor' => 0, 'polygon' => $square]])
        ->assertOk()->json('ids');

    expect($this->project->plan->items()->where('type', 'green')->pluck('id')->all())->toBe([$ids["item-{$id}"]]);
});

it('keeps the units an editor left out: added meanwhile, elsewhere', function () {
    $unit = aUnit(aUnitType($this->project));

    savePlan($this, [])->assertOk();

    expect($unit->planItem()->exists())->toBeTrue();
});

it('makes no shape a unit unless it is one', function () {
    $green = $this->project->plan->items()->create(['type' => 'green', 'floor' => 0, 'polygon' => [[0, 0], [9, 0], [9, 9]]]);

    savePlan($this, [['id' => $green->id, 'type' => 'unit', 'floor' => 0, 'polygon' => [[0, 0], [9, 0], [9, 9]]]])
        ->assertJsonValidationErrors('items');
});

describe('One person drawing it at a time', function () {
    beforeEach(function () {
        $this->other = anAdminOf($this->project);
        $this->other->update(['firstname' => 'Ana', 'lastname' => 'Pérez']);
    });

    it('tells a second admin who is drawing, and keeps their save from writing over it', function () {
        $this->as($this->other, $this->project)->postJson(route('manage.plan.lock'))->assertOk();

        $this->as($this->admin, $this->project)->postJson(route('manage.plan.lock'))
            ->assertStatus(423)->assertJson(['editor' => 'Ana Pérez']);
        $this->as($this->admin, $this->project)->putJson(route('manage.plan.update'), [
            'width' => 800, 'height' => 600, 'polygon' => [[0, 0], [800, 0], [800, 600], [0, 600]],
            'items' => [['type' => 'green', 'floor' => 0, 'polygon' => [[0, 0], [50, 0], [50, 50]]]],
        ])->assertStatus(423);

        expect($this->project->plan->items()->where('type', 'green')->exists())->toBeFalse();
    });

    it('lets the next one draw once the first is done', function () {
        $this->as($this->other, $this->project)->postJson(route('manage.plan.lock'))->assertOk();
        $this->as($this->other, $this->project)->deleteJson(route('manage.plan.unlock'))->assertOk();

        $this->as($this->admin, $this->project)->postJson(route('manage.plan.lock'))->assertOk();
    });

    it('lets go by itself of a plan left open in a window closed or cut off', function () {
        $this->as($this->other, $this->project)->postJson(route('manage.plan.lock'))->assertOk();

        $this->travel(App\Services\Plan\EditingLock::SECONDS + 1)->seconds();

        $this->as($this->admin, $this->project)->postJson(route('manage.plan.lock'))->assertOk();
    });

    it('keeps the plan for whoever is still drawing it, as their editor renews it', function () {
        $this->as($this->other, $this->project)->postJson(route('manage.plan.lock'))->assertOk();
        $this->travel(60)->seconds();
        $this->as($this->other, $this->project)->postJson(route('manage.plan.lock'))->assertOk();
        $this->travel(60)->seconds();

        $this->as($this->admin, $this->project)->postJson(route('manage.plan.lock'))->assertStatus(423);
    });
});

it('opens the plan for drawing at its own address, for its admins only', function () {
    expect($this->as($this->admin, $this->project)->get(route('units.edit'))->assertOk()->inertiaProps('editPlan'))->toBeTrue()
        ->and($this->get(route('units.index'))->inertiaProps('editPlan'))->toBeFalse();

    $this->as(aMemberOf(aFamily($this->project)), $this->project)->get(route('units.edit'))->assertForbidden();
});
