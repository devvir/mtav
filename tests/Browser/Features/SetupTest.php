<?php

use App\Models\Family;
use App\Models\Unit;
use App\Models\UnitType;

/**
 * Setting up a project: unit types, units, families.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->as(anAdminOf($this->project));
});

it('manages unit types, refusing duplicates and removing only unused ones', function () {
    aUnitType($this->project, ['name' => 'Studio']);

    visit(route('manage.unit_types.create', $this->project))
        ->fill('name', 'Studio')
        ->click('Add the unit type')
        ->assertSee('The name has already been taken.')
        ->fill('name', '2 bedrooms')
        ->fill('description', 'Two bedroom apartment')
        ->click('Add the unit type')
        ->assertSee('The unit type was added.')
        ->click('[aria-label="Remove Studio"]')
        ->click(inDialog('Remove'))
        ->assertSee('The unit type was removed.')
        ->assertNoSmoke();

    expect(UnitType::pluck('name')->all())->toBe(['2 bedrooms']);
});

it('does not offer to remove a unit type in use', function () {
    $type = aUnitType($this->project, ['name' => 'Studio']);
    aFamily($this->project, $type);

    $page = visit(route('manage.unit_types.index', $this->project));

    expect($page->script('document.querySelector(\'[aria-label="Remove Studio"]\').disabled'))->toBeTrue();
});

it('adds units one after another, creating a type on the way', function () {
    visit(route('manage.units.create', $this->project))
        ->fill('identifier', 'A-101')
        ->fill('[name="new_unit_type[name]"]', 'Penthouse')
        ->click('Add and continue with another')
        ->assertSee('The unit was added.')
        ->fill('identifier', 'A-102')
        ->click('Add and continue with another')
        // Saved once the form is cleared for the next one
        ->assertValue('[name="identifier"]', '')
        ->fill('identifier', 'A-101')
        ->click('Add the unit')
        ->assertSee('The identifier has already been taken.')
        ->assertNoSmoke();

    $penthouse = UnitType::firstWhere('name', 'Penthouse');

    expect(Unit::where('unit_type_id', $penthouse->id)->pluck('identifier')->sort()->values()->all())->toBe(['A-101', 'A-102']);
});

it('edits and removes units, but not one already given to a family', function () {
    $type = aUnitType($this->project);
    $free = aUnit($type, ['identifier' => 'A-101']);
    aUnit($type, ['identifier' => 'A-102', 'family_id' => aFamily($this->project, $type)->id]);

    $page = visit(route('manage.units.edit', [$this->project, $free]))
        ->fill('identifier', 'B-101')
        ->click('Save')
        ->assertSee('The unit was saved.')
        ->assertSee('B-101');

    expect($page->script('document.querySelector(\'[aria-label="Remove A-102"]\').disabled'))->toBeTrue();

    $page->click('[aria-label="Remove B-101"]')
        ->click(inDialog('Remove'))
        ->assertSee('The unit was removed.');

    expect(Unit::pluck('identifier')->all())->toBe(['A-102']);
});

it('registers, edits and removes families', function () {
    aUnitType($this->project, ['name' => '2 bedrooms']);
    aUnitType($this->project, ['name' => '3 bedrooms']);

    visit(route('manage.families.create', $this->project))
        ->fill('name', 'García')
        ->click('Add the family')
        ->assertSee('The family was added.')
        ->click('[aria-label="Edit García"]')
        ->select('unit_type_id', (string) UnitType::firstWhere('name', '3 bedrooms')->id)
        ->fill('name', 'García Pérez')
        ->click('Save')
        ->assertSee('The family was saved.')
        ->click('[aria-label="Remove García Pérez"]')
        ->assertSee('Its members leave the project')
        ->click(inDialog('Remove'))
        ->assertSee('The family was removed.')
        ->assertNoSmoke();

    expect(Family::count())->toBe(0);
});

it('guides to unit types before families', function () {
    visit(route('manage.families.create', $this->project))
        ->assertSee('There are no unit types yet.')
        ->click('Add one first')
        ->assertSee('Add a unit type');
});

it('walks the admin from an empty project to the lottery', function () {
    visit(route('manage.overview', $this->project))
        ->assertSee('Getting to the lottery')
        ->click('Add the unit types')
        ->assertSee('No unit types yet');
});
