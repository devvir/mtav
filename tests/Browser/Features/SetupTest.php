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

    expect($page->script('document.querySelector(\'[aria-label="Remove Studio"]\').getAttribute("aria-disabled")'))->toBe('true');

    // It stays reachable, to say why, and pressing it removes nothing
    $page->keys('[aria-label="Remove Studio"]', 'Shift')
        ->assertSee('In use by units or families');
    $page->script('() => document.querySelector(\'[aria-label="Remove Studio"]\').click()');
    $page->wait(0.3)->assertMissing('[role="dialog"]');
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
        ->click('Add the first family')
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
        ->click('Add the first family')
        ->fill('name', 'García')
        ->fill('email', 'garcia@example.com')
        ->click(inDialog('Add and invite'))
        ->assertSee('The family was added.')
        ->click('Edit')
        ->select(inDialogField('unit_type_id'), (string) UnitType::firstWhere('name', '3 bedrooms')->id)
        ->fill(inDialogField('name'), 'García Pérez')
        ->click(inDialog('Save'))
        ->assertSee('The family was saved.')
        ->assertSee('García Pérez')
        ->click(button('Remove'))
        ->assertSee('Invitations waiting to be accepted are withdrawn.')
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
        ->click('.note-mini')
        ->click('Add the unit types')
        ->assertPresent('[role="dialog"] [name="name"]');
});

it('opens unit types, units and families for editing from their lists', function () {
    $type = aUnitType($this->project, ['name' => 'Studio']);
    $unit = aUnit($type, ['identifier' => 'A-101']);
    $family = aFamily($this->project, $type, ['name' => 'García']);

    visit(route('units.index', $this->project))
        ->click('[aria-label="Edit Studio"]')
        ->assertValue('[role="dialog"] [name="name"]', 'Studio')
        // Renamed there and then
        ->fill('[role="dialog"] [name="name"]', 'Studio apartment')
        ->click(inDialog('Save'))
        ->assertSee('Studio apartment')
        ->assertNoSmoke();
    expect($type->fresh()->name)->toBe('Studio apartment');

    visit(route('units.show', [$this->project, $unit]))
        ->click('Edit')
        ->assertValue('[role="dialog"] [name="identifier"]', 'A-101');

    visit(route('families.show', [$this->project, $family]))
        ->click('Edit')
        ->assertValue('[role="dialog"] [name="name"]', 'García');
});

it('shows a project\'s admins its room on asking, never a warning on every page', function () {
    $project = anUnapprovedProject();
    $this->as(anAdminOf($project));

    visit(route('units.index', $project))->assertNotPresent('.shell-banner');
    visit(route('manage.overview', $project))
        ->assertDontSee('What the project has room for:')
        ->click('button[aria-expanded]:has-text("Limited")')
        ->assertSee('What the project has room for:')
        ->assertSee('/ 50');
});

it('says nothing about room in an unlimited project', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('manage.overview', $project))
        ->assertPresent('.note-mini')
        ->assertDontSee('Unlimited')
        ->assertNotPresent('button[aria-expanded]:has-text("Limited")');
});

it('offers the people\'s list without their email, phone or ID number, saying consent comes first', function () {
    $project = aProject();
    $this->as(anAdminOf($project));
    $href = fn ($page) => $page->script('() => [...document.querySelectorAll("a[download]")].find((link) => link.textContent.trim() === "People").getAttribute("href")');

    $page = visit(route('manage.settings.edit', $project))
        ->assertSee('Personal data must never be used or shared without people\'s consent.');
    expect($href($page))->not->toContain('without_personal_data');

    $page->click('label:has-text("Without email, phone or ID number")')->assertNoSmoke();
    expect($href($page))->toContain('without_personal_data=1');

    // Each list downloads as a spreadsheet: its first line names its columns; the people's, without
    // personal data, leaves the email out
    $download = fn (string $label) => $page->script(<<<JS
        async () => {
            const link = [...document.querySelectorAll("a[download]")].find((one) => one.textContent.trim() === "{$label}");
            const response = await fetch(link.href);
            const text = await response.text();
            return { status: response.status, type: response.headers.get("content-type"), first: text.replace(/^\uFEFF/, "").split("\n")[0] };
        }
        JS);
    foreach (['People', 'Families', 'Units'] as $list) {
        $answer = $download($list);
        expect($answer['status'])->toBe(200)
            ->and($answer['type'])->toContain('csv')
            ->and($answer['first'])->not->toBe('');
    }
    expect(strtolower($download('People')['first']))->not->toContain('email');
    $page->click('label:has-text("Without email, phone or ID number")');
    expect(strtolower($download('People')['first']))->toContain('email');
});
