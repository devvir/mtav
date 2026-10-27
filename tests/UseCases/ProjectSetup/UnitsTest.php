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

    it('creates a unit together with a new unit type', function () {
        $this->as($this->admin, $this->project)->post(route('manage.units.store'), [
            'identifier'    => 'Ap. 201',
            'new_unit_type' => ['name' => 'Penthouse', 'description' => 'Top floor'],
        ])->assertSessionHasNoErrors();

        $unit = $this->project->units()->firstWhere('identifier', 'Ap. 201');

        expect($unit?->type)->name->toBe('Penthouse')->project_id->toBe($this->project->id);
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
        $this->as($this->member, $this->project)->get(route('units.show', $unit))->assertOk();
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
