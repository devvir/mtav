<?php

use App\Models\Unit;

/**
 * Admins load the concrete units of each unit type. Units stay unassigned until the
 * lottery runs; every unit gets a place on the project plan.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
});

describe('An admin', function () {
    it('creates a unit of an existing type, placed on the project plan', function () {
        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 101',
            'unit_type_id' => $this->type->id,
        ])->assertSessionHasNoErrors();

        $unit = $this->project->units()->firstWhere('identifier', 'Ap. 101');

        expect($unit)->not->toBeNull()
            ->unit_type_id->toBe($this->type->id)
            ->family_id->toBeNull()
            ->and($unit->planItem->plan_id)->toBe($this->project->plan->id);
    });

    it('creates a unit where it was drawn on the plan, on top, answering its shape', function () {
        $polygon = [[100, 100], [160, 100], [160, 150], [100, 150]];

        $response = $this->as($this->admin, $this->project)->postJson(route('manage.units.store'), [
            'identifier'   => 'Ap. 301',
            'unit_type_id' => $this->type->id,
            'polygon'      => $polygon,
            'floor'        => 1,
        ])->assertCreated();

        $item = $this->project->units()->firstWhere('identifier', 'Ap. 301')->planItem;

        expect($item)->polygon->toEqual($polygon)->floor->toBe(1)
            ->position->toBe($this->project->plan->items()->max('position'))
            ->and($response->json('item'))->id->toBe($item->id)->unit->identifier->toBe('Ap. 301');
    });

    it('refuses a drawn unit with too few corners', function () {
        $this->as($this->admin, $this->project)->postJson(route('manage.units.store'), [
            'identifier'   => 'Ap. 302',
            'unit_type_id' => $this->type->id,
            'polygon'      => [[0, 0], [10, 0]],
        ])->assertJsonValidationErrors('polygon');
    });

    it('lets a unit added here wait beside a drawn plan, rather than land on the drawing', function () {
        $this->project->plan->items()->create(['type' => 'green', 'floor' => 0, 'polygon' => [[0, 0], [50, 0], [50, 50]]]);

        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 401',
            'unit_type_id' => $this->type->id,
        ]);

        expect($this->project->units()->firstWhere('identifier', 'Ap. 401')->planItem->placed)->toBeFalse();
    });

    it('places a unit added here at once while nothing is drawn', function () {
        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 402',
            'unit_type_id' => $this->type->id,
        ]);

        expect($this->project->units()->firstWhere('identifier', 'Ap. 402')->planItem->placed)->toBeTrue();
    });

    it('creates a unit together with a new unit type', function () {
        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'    => 'Ap. 201',
            'new_unit_type' => ['name' => 'Penthouse', 'description' => 'Top floor'],
        ])->assertSessionHasNoErrors();

        $unit = $this->project->units()->firstWhere('identifier', 'Ap. 201');

        expect($unit?->type)->name->toBe('Penthouse')->project_id->toBe($this->project->id);
    });

    it('refuses a shape of too many corners before looking at any of them', function () {
        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 501',
            'unit_type_id' => $this->type->id,
            'polygon'      => array_fill(0, 50_000, ['x', 'y']),
        ])->assertSessionHasErrors('polygon')->assertSessionDoesntHaveErrors('polygon.0');

        expect($this->project->units()->where('identifier', 'Ap. 501')->exists())->toBeFalse();
    });

    it('changes a unit\'s identifier and type', function () {
        $unit = aUnit($this->type);
        $otherType = aUnitType($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $unit), [
            'identifier'   => 'Ap. 102',
            'unit_type_id' => $otherType->id,
        ])->assertSessionHasNoErrors();

        expect($unit->fresh())->identifier->toBe('Ap. 102')->unit_type_id->toBe($otherType->id);
    });

    it('deletes an unassigned unit', function () {
        $unit = aUnit($this->type);

        $this->as($this->admin, $this->project)->delete(route('manage.units.destroy', $unit));

        expect(Unit::find($unit->id))->toBeNull();
    });

    it('cannot delete a unit already assigned to a family', function () {
        $family = aFamily($this->project, $this->type);
        $unit = aUnit($this->type, ['family_id' => $family->id]);

        $this->as($this->admin, $this->project)->delete(route('manage.units.destroy', $unit));

        expect(Unit::find($unit->id))->not->toBeNull();
    });

    it('cannot give a unit a type from another project', function () {
        $foreignType = aUnitType(aProject());

        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 301',
            'unit_type_id' => $foreignType->id,
        ])->assertSessionHasErrors('unit_type_id');

        expect($this->project->units()->where('identifier', 'Ap. 301')->exists())->toBeFalse();
    });

    it('cannot reuse a unit identifier within the same project', function () {
        aUnit($this->type, ['identifier' => 'Ap. 101']);

        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'Ap. 101',
            'unit_type_id' => $this->type->id,
        ])->assertSessionHasErrors('identifier');

        expect($this->project->units()->where('identifier', 'Ap. 101')->count())->toBe(1);
    });

    it('cannot change units of a project they do not manage', function () {
        $foreignUnit = aUnit(aUnitType(aProject()), ['identifier' => 'Original']);

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $foreignUnit), [
            'identifier' => 'Hijacked',
        ])->assertNotFound();

        $this->as($this->admin, $this->project)
            ->delete(route('manage.units.destroy', $foreignUnit))
            ->assertNotFound();

        expect($foreignUnit->fresh())->identifier->toBe('Original');
    });
});

describe('A member', function () {
    beforeEach(function () {
        $this->member = aMemberOf(aFamily($this->project, $this->type));
    });

    it('sees the units of their project', function () {
        $unit = aUnit($this->type);

        $this->as($this->member, $this->project)->get(route('units.index'))->assertOk();
        $this->as($this->member, $this->project)->get(route('units.show', $unit))
            ->assertRedirect(route('units.index', ['unit' => $unit->id]));

        expect($this->get(route('units.index', ['unit' => $unit->id]))->inertiaProps('unit.id'))->toBe($unit->id);
    });

    it('cannot create, change or delete units', function () {
        $unit = aUnit($this->type, ['identifier' => 'Original']);

        expect($this->as($this->member, $this->project)->post(route('manage.units.store'), [
            'identifier'   => 'New',
            'unit_type_id' => $this->type->id,
        ]))->toBeDenied();

        expect($this->as($this->member, $this->project)->put(route('manage.units.update', $unit), [
            'identifier'   => 'Changed',
            'unit_type_id' => $this->type->id,
        ]))->toBeDenied();

        expect($this->as($this->member, $this->project)->delete(route('manage.units.destroy', $unit)))
            ->toBeDenied();

        expect($this->project->units()->where('identifier', 'New')->exists())->toBeFalse()
            ->and($unit->fresh())->identifier->toBe('Original');
    });
});

describe('Changing a unit', function () {
    it('moves it to a type that exists, never to one made on the way, and keeps it as it was when asked to', function () {
        $unit = aUnit($this->type, ['identifier' => 'Ap. 101']);

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $unit), [
            'identifier'    => 'Ap. 102',
            'unit_type_id'  => null,
            'new_unit_type' => ['name' => 'Penthouse'],
        ])->assertSessionHasErrors(['unit_type_id', 'new_unit_type']);

        expect($unit->fresh())->identifier->toBe('Ap. 101')->unit_type_id->toBe($this->type->id)
            ->and($this->project->unitTypes()->where('name', 'Penthouse')->exists())->toBeFalse();
    });

    it('takes a change as the form sends it, with no new type in it', function () {
        $unit = aUnit($this->type, ['identifier' => 'Ap. 101']);

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $unit), [
            'identifier'    => 'Ap. 102',
            'unit_type_id'  => $this->type->id,
            'new_unit_type' => null,
        ])->assertSessionHasNoErrors();

        expect($unit->fresh())->identifier->toBe('Ap. 102');
    });
});

describe('A unit\'s identifier', function () {
    it('is refused, with a message, when another unit has it in other capitals or accents', function (string $taken, string $given) {
        aUnit($this->type, ['identifier' => $taken]);
        $other = aUnit($this->type, ['identifier' => 'B-1']);
        $this->as($this->admin, $this->project);

        $this->post(route('manage.units.store'), ['identifier' => $given, 'unit_type_id' => $this->type->id])
            ->assertSessionHasErrors(['identifier' => __('validation.unique', ['attribute' => __('validation.attributes.identifier')])]);
        $this->put(route('manage.units.update', $other), ['identifier' => $given, 'unit_type_id' => $this->type->id])
            ->assertSessionHasErrors('identifier');

        expect($this->project->units()->count())->toBe(2)->and($other->fresh()->identifier)->toBe('B-1');
    })->with([['A-101', 'a-101'], ['Café 1', 'cafe 1']]);

    it('may be a unit\'s own, in other capitals', function () {
        $unit = aUnit($this->type, ['identifier' => 'a-101']);

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $unit), ['identifier' => 'A-101', 'unit_type_id' => $this->type->id])
            ->assertSessionHasNoErrors();

        expect($unit->fresh()->identifier)->toBe('A-101');
    });
});

describe('An unapproved project\'s units', function () {
    beforeEach(function () {
        $this->project = anUnapprovedProject();
        $this->admin = $this->project->admins()->first();
        $this->type = aUnitType($this->project);
        $this->max = config('limits.unapproved.units');
    });

    it('take as many as its limit, and refuse one more, saying so', function () {
        foreach (range(1, $this->max - 1) as $n) {
            $this->project->units()->create(['unit_type_id' => $this->type->id, 'identifier' => "U-{$n}"]);
        }
        $this->as($this->admin, $this->project);

        $this->post(route('manage.units.store'), ['identifier' => 'Last', 'unit_type_id' => $this->type->id])->assertSessionHasNoErrors();
        $this->post(route('manage.units.store'), ['identifier' => 'One more', 'unit_type_id' => $this->type->id])
            ->assertSessionHasErrors(['identifier' => __('validation.limit_units', ['max' => $this->max])]);

        expect($this->project->units()->count())->toBe($this->max)
            ->and($this->project->units()->where('identifier', 'One more')->exists())->toBeFalse();
    });

    it('can still be changed at the limit', function () {
        foreach (range(1, $this->max) as $n) {
            $this->project->units()->create(['unit_type_id' => $this->type->id, 'identifier' => "U-{$n}"]);
        }
        $unit = $this->project->units()->firstWhere('identifier', 'U-1');

        $this->as($this->admin, $this->project)->put(route('manage.units.update', $unit), ['identifier' => 'U-0', 'unit_type_id' => $this->type->id])
            ->assertSessionHasNoErrors();

        expect($unit->fresh()->identifier)->toBe('U-0');
    });
});

it('takes an identifier of its longest, in characters of four bytes, and refuses one more', function () {
    $this->as($this->admin, $this->project);

    $this->post(route('manage.units.store'), ['identifier' => str_repeat('😀', 81), 'unit_type_id' => $this->type->id])->assertSessionHasErrors('identifier');
    $this->post(route('manage.units.store'), ['identifier' => str_repeat('😀', 80), 'unit_type_id' => $this->type->id])->assertSessionHasNoErrors();

    expect($this->project->units()->where('identifier', str_repeat('😀', 80))->exists())->toBeTrue();
});
