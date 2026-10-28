<?php

use App\Models\Family;

/**
 * Admins register the families of a project and give each one a unit type: the family
 * (not the individual) is the lottery participant, competing only for units of its type.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->type = aUnitType($this->project);
});

describe('An admin', function () {
    it('registers a family with a unit type', function () {
        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name'         => 'García',
            'unit_type_id' => $this->type->id,
        ])->assertSessionHasNoErrors();

        expect($this->project->families()->firstWhere('name', 'García'))
            ->not->toBeNull()
            ->unit_type_id->toBe($this->type->id);
    });

    it('sees a newly registered family in the families list, before it has members', function () {
        $family = aFamily($this->project, $this->type);

        $listed = $this->as($this->admin, $this->project)->get(route('families.index'))->inertiaProps('families.data');

        expect(collect($listed)->pluck('id'))->toContain($family->id);
    });

    it('changes a family\'s name and unit type', function () {
        $family = aFamily($this->project, $this->type);
        $otherType = aUnitType($this->project);

        $this->as($this->admin, $this->project)->put(route('manage.families.update', $family), [
            'name'         => 'Renamed',
            'unit_type_id' => $otherType->id,
        ])->assertSessionHasNoErrors();

        expect($family->fresh())->name->toBe('Renamed')->unit_type_id->toBe($otherType->id);
    });

    it('removes a family together with its members', function () {
        $family = aFamily($this->project, $this->type);
        $member = aMemberOf($family);

        $this->as($this->admin, $this->project)->delete(route('manage.families.destroy', $family));

        expect(Family::find($family->id))->toBeNull()
            ->and($this->project->members()->whereKey($member->id)->exists())->toBeFalse();
    });

    it('cannot give a family a unit type from another project', function () {
        $foreignType = aUnitType(aProject());

        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name'         => 'Foreign',
            'unit_type_id' => $foreignType->id,
        ])->assertSessionHasErrors('unit_type_id');

        expect($this->project->families()->where('name', 'Foreign')->exists())->toBeFalse();
    });

    it('cannot reuse a family name within the same project', function () {
        aFamily($this->project, $this->type, ['name' => 'García']);

        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name'         => 'García',
            'unit_type_id' => $this->type->id,
        ])->assertSessionHasErrors('name');

        expect($this->project->families()->where('name', 'García')->count())->toBe(1);
    });

    it('cannot change families of a project they do not manage', function () {
        $otherProject = aProject();
        $foreignFamily = aFamily($otherProject, null, ['name' => 'Original']);

        $this->as($this->admin, $this->project)->put(route('manage.families.update', $foreignFamily), [
            'name'         => 'Hijacked',
            'unit_type_id' => $foreignFamily->unit_type_id,
        ])->assertNotFound();

        $this->as($this->admin, $this->project)
            ->delete(route('manage.families.destroy', $foreignFamily))
            ->assertNotFound();

        expect($foreignFamily->fresh())->name->toBe('Original');
    });
});

describe('A member', function () {
    beforeEach(function () {
        $this->family = aFamily($this->project, $this->type);
        $this->member = aMemberOf($this->family);
    });

    it('sees the families of their project', function () {
        $other = aFamily($this->project, $this->type);
        aMemberOf($other);

        $this->as($this->member, $this->project)->get(route('families.show', $other))
            ->assertRedirect(route('families.index', ['family' => $other->id]));

        expect($this->get(route('families.index', ['family' => $other->id]))->inertiaProps('family.id'))
            ->toBe($other->id);
    });

    it('cannot register or remove families', function () {
        expect($this->as($this->member, $this->project)->post(route('manage.families.store'), [
            'name'         => 'New',
            'unit_type_id' => $this->type->id,
        ]))->toBeDenied();

        expect($this->as($this->member, $this->project)->delete(route('manage.families.destroy', $this->family)))
            ->toBeDenied();

        expect($this->project->families()->where('name', 'New')->exists())->toBeFalse();
    });

    it('cannot change their family\'s unit type (it decides which units they compete for)', function () {
        $otherType = aUnitType($this->project);

        $this->as($this->member, $this->project)->put(route('manage.families.update', $this->family), [
            'name'         => $this->family->name,
            'unit_type_id' => $otherType->id,
        ]);

        expect($this->family->fresh()->unit_type_id)->toBe($this->type->id);
    });

    it('cannot change another family', function () {
        $other = aFamily($this->project, $this->type, ['name' => 'Original']);

        expect($this->as($this->member, $this->project)->put(route('manage.families.update', $other), [
            'name'         => 'Changed',
            'unit_type_id' => $this->type->id,
        ]))->toBeDenied();

        expect($other->fresh()->name)->toBe('Original');
    });
});
