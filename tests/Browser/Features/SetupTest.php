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

    visit(route('units.index', [$this->project, 'new_type' => 1]))
        ->fill('name', 'Studio')
        ->click(inDialog('Add the unit type'))
        ->assertSee('The name has already been taken.')
        ->fill('name', '2 bedrooms')
        ->fill('description', 'Two bedroom apartment')
        ->click(inDialog('Add the unit type'))
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

    $page = visit(route('units.index', $this->project))->assertSee('Studio');

    expect($page->script('document.querySelector(\'[aria-label="Remove Studio"]\').disabled'))->toBeTrue();
});

it('adds units one after another, creating a type on the way', function () {
    visit(route('units.index', [$this->project, 'new_unit' => 1]))
        ->fill('identifier', 'A-101')
        ->fill('[name="new_unit_type[name]"]', 'Penthouse')
        ->click('Add and continue with another')
        ->assertSee('The unit was added.')
        ->fill('identifier', 'A-102')
        ->click('Add and continue with another')
        // Saved once the form is cleared for the next one
        ->assertValue('[name="identifier"]', '')
        ->fill('identifier', 'A-101')
        ->click(inDialog('Add the unit'))
        ->assertSee('The identifier has already been taken.')
        ->assertNoSmoke();

    $penthouse = UnitType::firstWhere('name', 'Penthouse');

    expect(Unit::where('unit_type_id', $penthouse->id)->pluck('identifier')->sort()->values()->all())->toBe(['A-101', 'A-102']);
});

it('switches between an existing type and a new one while adding a unit', function () {
    $studio = aUnitType($this->project, ['name' => 'Studio']);

    visit(route('units.index', $this->project))
        ->click('Add units')
        ->click('Or create a new type')
        ->fill('[name="new_unit_type[name]"]', 'Loft')
        ->click('Use an existing type')
        ->assertNotPresent('[name="new_unit_type[name]"]')
        ->fill('identifier', 'C-301')
        ->click(inDialog('Add the unit'))
        ->assertSee('The unit was added.');

    expect(Unit::firstWhere('identifier', 'C-301')->unit_type_id)->toBe($studio->id)
        ->and(UnitType::where('name', 'Loft')->exists())->toBeFalse();
});

it('starts empty lists from their first item', function () {
    visit(route('units.index', $this->project))
        ->click('Add the first unit type')
        ->assertPresent('[role="dialog"] [name="name"]');

    aUnitType($this->project);

    visit(route('families.index', $this->project))
        ->click('Register the first family')
        ->assertPresent('[role="dialog"] [name="name"]');
});

it('edits and removes units, but not one already given to a family', function () {
    $type = aUnitType($this->project);
    $free = aUnit($type, ['identifier' => 'A-101']);
    aUnit($type, ['identifier' => 'A-102', 'family_id' => aFamily($this->project, $type)->id]);

    visit(route('units.show', [$this->project, $free]))
        ->click('Edit')
        ->fill('identifier', 'B-101')
        ->click(inDialog('Save'))
        ->assertSee('The unit was saved.')
        ->assertSee('B-101')
        ->click(button('Remove'))
        ->click(inDialog('Remove'))
        ->assertSee('The unit was removed.')
        ->assertPathIs(route('units.index', $this->project, absolute: false));

    $given = visit(route('units.show', [$this->project, Unit::firstWhere('identifier', 'A-102')]))
        ->assertSee('A-102');

    expect($given->script("[...document.querySelectorAll('button')].find((b) => b.textContent.trim() === 'Remove').disabled"))->toBeTrue();

    expect(Unit::pluck('identifier')->all())->toBe(['A-102']);
});

it('registers, edits and removes families', function () {
    aUnitType($this->project, ['name' => '2 bedrooms']);
    aUnitType($this->project, ['name' => '3 bedrooms']);

    visit(route('families.index', $this->project))
        ->click('Register the first family')
        ->fill('name', 'García')
        ->click(inDialog('Add the family'))
        ->assertSee('The family was added.')
        ->click('Edit')
        ->select(inDialogField('unit_type_id'), (string) UnitType::firstWhere('name', '3 bedrooms')->id)
        ->fill(inDialogField('name'), 'García Pérez')
        ->click(inDialog('Save'))
        ->assertSee('The family was saved.')
        ->assertSee('García Pérez')
        ->click(button('Remove'))
        ->assertSee('Its members leave the project')
        ->click(inDialog('Remove'))
        ->assertSee('The family was removed.')
        ->assertNoSmoke();

    expect(Family::count())->toBe(0);
});

it('guides to unit types before families', function () {
    visit(route('families.index', [$this->project, 'new' => 1]))
        ->assertSee('There are no unit types yet.')
        ->click('Add one first')
        ->assertSee('Add a unit type');
});

it('walks the admin from an empty project to the lottery', function () {
    visit(route('manage.overview', $this->project))
        ->assertSee('Getting to the lottery')
        ->click('Add the unit types')
        ->assertPresent('[role="dialog"] [name="name"]');
});

it('opens unit types, units and families for editing from their lists', function () {
    $type = aUnitType($this->project, ['name' => 'Studio']);
    $unit = aUnit($type, ['identifier' => 'A-101']);
    $family = aFamily($this->project, $type, ['name' => 'García']);

    visit(route('units.index', $this->project))
        ->click('[aria-label="Edit Studio"]')
        ->assertValue('[role="dialog"] [name="name"]', 'Studio');

    visit(route('units.show', [$this->project, $unit]))
        ->click('Edit')
        ->assertValue('[role="dialog"] [name="identifier"]', 'A-101');

    visit(route('families.show', [$this->project, $family]))
        ->click('Edit')
        ->assertValue('[role="dialog"] [name="name"]', 'García');
});

it('leads an unapproved project\'s admins to what the limits are', function () {
    $project = anUnapprovedProject();
    $this->as(anAdminOf($project));

    visit(route('units.index', $project))
        ->assertSee('This project is awaiting approval: some limits apply.')
        ->click('.shell-banner >> text=Details')
        ->assertPathIs(route('manage.overview', $project, absolute: false));
});
