<?php

use App\Models\UnitType;

/**
 * Admins define the unit types of the projects they manage. A unit type partitions
 * the lottery: each family receives a unit of its own type. A type may carry the size its
 * units start at on the plan.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
});

describe('An admin', function () {
    it('creates a unit type in their project', function () {
        $this->as($this->admin, $this->project)->post(route('manage.unit_types.store'), [
            'name'        => '2 Bedrooms',
            'description' => 'Two bedroom apartment',
        ])->assertSessionHasNoErrors();

        expect($this->project->unitTypes()->where('name', '2 Bedrooms')->exists())->toBeTrue();
    });

    it('gives a type the size its new units start at on the plan', function () {
        $type = aUnitType($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.unit_types.update', $type), [
            'name'        => $type->name,
            'plan_width'  => 40,
            'plan_height' => 25,
        ])->assertSessionHasNoErrors();
        $this->post(route('manage.units.store'), ['identifier' => 'C-1', 'unit_type_id' => $type->id]);

        $box = $this->project->units()->firstWhere('identifier', 'C-1')->planItem->polygon;
        expect($box[1][0] - $box[0][0])->toEqual(40)->and($box[2][1] - $box[1][1])->toEqual(25);
    });

    it('takes a size on the plan whole, or none', function () {
        $type = aUnitType($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.unit_types.update', $type), [
            'name'       => $type->name,
            'plan_width' => 40,
        ])->assertSessionHasErrors('plan_height');
    });

    it('renames a unit type', function () {
        $type = aUnitType($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.unit_types.update', $type), [
            'name'        => '3 Bedrooms',
            'description' => 'Three bedroom apartment',
        ])->assertSessionHasNoErrors();

        expect($type->fresh()->name)->toBe('3 Bedrooms');
    });

    it('deletes an unused unit type', function () {
        $type = aUnitType($this->project);

        $this->as($this->admin, $this->project)->delete(route('manage.unit_types.destroy', $type));

        expect(UnitType::find($type->id))->toBeNull();
    });

    it('cannot delete a unit type that families are assigned to', function () {
        $type = aUnitType($this->project);
        aFamily($this->project, $type);

        $this->as($this->admin, $this->project)->delete(route('manage.unit_types.destroy', $type));

        expect(UnitType::find($type->id))->not->toBeNull();
    });

    it('cannot delete a unit type that has units', function () {
        $type = aUnitType($this->project);
        aUnit($type);

        $this->as($this->admin, $this->project)->delete(route('manage.unit_types.destroy', $type));

        expect(UnitType::find($type->id))->not->toBeNull();
    });

    it('cannot reuse a unit type name within the same project', function () {
        aUnitType($this->project, ['name' => 'Studio']);

        $this->as($this->admin, $this->project)->post(route('manage.unit_types.store'), [
            'name'        => 'Studio',
            'description' => 'Another studio',
        ])->assertSessionHasErrors('name');

        expect($this->project->unitTypes()->where('name', 'Studio')->count())->toBe(1);
    });

    it('cannot change unit types of a project they do not manage', function () {
        $otherProject = aProject();
        $type = aUnitType($otherProject, ['name' => 'Original']);

        $this->as($this->admin, $this->project)
            ->put(route('manage.unit_types.update', $type), ['name' => 'Hijacked', 'description' => 'x'])
            ->assertNotFound();

        $this->as($this->admin, $this->project)
            ->delete(route('manage.unit_types.destroy', $type))
            ->assertNotFound();

        expect($type->fresh())->name->toBe('Original');
    });
});

describe('A member', function () {
    beforeEach(function () {
        $this->member = aMemberOf(aFamily($this->project));
    });

    it('sees the unit types of their project', function () {
        $type = aUnitType($this->project);

        $this->as($this->member, $this->project)
            ->get(route('units.index'))
            ->assertOk();
    });

    it('cannot create, change or delete unit types', function () {
        $type = aUnitType($this->project, ['name' => 'Original']);

        expect($this->as($this->member, $this->project)->post(route('manage.unit_types.store'), [
            'name' => 'New', 'description' => 'New type',
        ]))->toBeDenied();

        expect($this->as($this->member, $this->project)->put(route('manage.unit_types.update', $type), [
            'name' => 'Changed', 'description' => 'x',
        ]))->toBeDenied();

        expect($this->as($this->member, $this->project)->delete(route('manage.unit_types.destroy', $type)))
            ->toBeDenied();

        expect($this->project->unitTypes()->where('name', 'New')->exists())->toBeFalse()
            ->and($type->fresh())->name->toBe('Original');
    });
});
