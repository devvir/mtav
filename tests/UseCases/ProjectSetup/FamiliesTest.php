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
            'email'        => 'garcia@example.com',
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

    it('refuses removing a family with people in it, and says what to do', function () {
        $family = aFamily($this->project, $this->type);
        $member = aMemberOf($family);

        $this->as($this->admin, $this->project)->delete(route('manage.families.destroy', $family))
            ->assertSessionHasErrors(['family' => __('validation.family_has_people')]);

        expect($family->fresh()->trashed())->toBeFalse()
            ->and($member->fresh()->familyIn($this->project)->id)->toBe($family->id);
    });

    it('refuses it when the only one in it is an admin too', function () {
        $family = aFamily($this->project, $this->type);
        $this->admin->membershipIn($this->project)->update(['family_id' => $family->id]);

        $this->as($this->admin, $this->project)->delete(route('manage.families.destroy', $family))
            ->assertSessionHasErrors(['family' => __('validation.family_has_people')]);

        expect($family->fresh()->trashed())->toBeFalse();
    });

    it('still archives a family when its last person leaves', function () {
        $family = aFamily($this->project, $this->type);
        $member = aMemberOf($family);

        $member->membershipIn($this->project)->delete();

        expect(Family::find($family->id))->toBeNull()
            ->and($family->fresh()->trashed())->toBeTrue();
    });

    it('cannot give a family a unit type from another project', function () {
        $foreignType = aUnitType(aProject());

        $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name'         => 'Foreign',
            'unit_type_id' => $foreignType->id,
        ])->assertSessionHasErrors('unit_type_id');

        expect($this->project->families()->where('name', 'Foreign')->exists())->toBeFalse();
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

describe('An unapproved project\'s families', function () {
    beforeEach(function () {
        $this->project = anUnapprovedProject();
        $this->admin = $this->project->admins()->first();
        $this->type = aUnitType($this->project);
        $this->max = config('limits.unapproved.families');
        $this->fill = function (int $count) {
            foreach (range(1, $count) as $n) {
                $this->project->families()->create(['name' => "Family {$n}", 'unit_type_id' => $this->type->id]);
            }
        };
        $this->full = __('validation.limit_families', ['max' => $this->max]);
    });

    it('take as many as its limit from an admin, and refuse one more, saying so', function () {
        ($this->fill)($this->max - 1);
        $register = fn (string $name) => $this->as($this->admin, $this->project)->post(route('manage.families.store'), [
            'name' => $name, 'unit_type_id' => $this->type->id, 'email' => strtolower($name) . '@example.com',
        ]);

        $register('Last')->assertSessionHasNoErrors();
        $register('Over')->assertSessionHasErrors(['name' => $this->full]);

        expect($this->project->families()->count())->toBe($this->max)
            ->and($this->project->families()->where('name', 'Over')->exists())->toBeFalse();
    });

    it('refuse one more started by someone taking part, saying so', function () {
        $this->project->update(['allows_guests' => true]);
        $guest = aUser();
        $this->project->memberships()->create(['user_id' => $guest->id]);
        ($this->fill)($this->max);

        $this->as($guest, $this->project)->post(route('families.store'), ['name' => 'Ours', 'unit_type_id' => $this->type->id])
            ->assertSessionHasErrors(['name' => $this->full]);

        expect($guest->fresh()->familyIn($this->project))->toBeNull()
            ->and($this->project->families()->count())->toBe($this->max);
    });

    it('take one started by someone taking part, up to the limit', function () {
        $this->project->update(['allows_guests' => true]);
        $guest = aUser();
        $this->project->memberships()->create(['user_id' => $guest->id]);
        ($this->fill)($this->max - 1);

        $this->as($guest, $this->project)->post(route('families.store'), ['name' => 'Ours', 'unit_type_id' => $this->type->id])
            ->assertSessionHasNoErrors();

        expect($guest->fresh()->familyIn($this->project)?->name)->toBe('Ours');
    });

    it('can still be changed at the limit', function () {
        ($this->fill)($this->max);
        $family = $this->project->families()->firstWhere('name', 'Family 1');

        $this->as($this->admin, $this->project)->put(route('manage.families.update', $family), ['name' => 'Renamed', 'unit_type_id' => $this->type->id])
            ->assertSessionHasNoErrors();

        expect($family->fresh()->name)->toBe('Renamed');
    });
});
