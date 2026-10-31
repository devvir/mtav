<?php

/**
 * A new project, before anything is in it: every page says what goes there, and who can
 * act sees how to start.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
});

it('walks admins from the home page to their first unit type', function () {
    $this->as($this->admin);

    visit(route('projects.show', $this->project))
        ->assertSee('Getting to the lottery')
        ->assertSee('No events coming up')
        ->assertSee('Nothing shared yet')
        ->assertNotPresent('.ds-stat');

    visit(route('units.index', $this->project))
        ->click('Add the first unit type')
        ->assertPresent('[role="dialog"] [name="name"]');
});

it('tells members their project is getting ready', function () {
    $member = aMemberOf(aFamily($this->project));
    $this->as($member);

    visit(route('projects.show', $this->project))
        ->assertSee('Your project is getting ready')
        ->assertDontSee('Getting to the lottery')
        ->navigate(route('units.index', $this->project))
        ->assertSee('No units yet')
        ->assertDontSee('Add the first unit type');
});

it('tells members where the families will show up', function () {
    $this->as(anAdminOf($this->project));
    $this->project->update(['allows_guests' => true]);
    $member = aUser();
    $this->project->memberships()->create(['user_id' => $member->id, 'is_admin' => false]);
    $this->as($member);

    visit(route('families.index', $this->project))
        ->assertSee('Families show up here as the admins register them.')
        ->assertNotPresent('[aria-label="Search families or people"]');
});

it('says when a search finds nothing', function () {
    aFamily($this->project, null, ['name' => 'García']);
    $this->as($this->admin);

    visit(route('families.index', [$this->project, 'q' => 'Zubizarreta']))
        ->assertSee('No family or person matches “Zubizarreta”.')
        ->assertPresent('[aria-label="Search families or people"]');
});

it('shows the families on the home page as soon as there are any, units or not, people or not', function () {
    aFamily($this->project, aUnitType($this->project), ['name' => 'F01']);
    $this->as($this->admin);

    visit(route('projects.show', $this->project))
        ->assertPresent('.ds-stat')
        ->assertSeeIn('.stat-link:first-child', '1')
        ->assertSee('F01')
        ->assertNoSmoke();
});

it('puts cooperatives\' photos behind a home with nothing shared, and offers to share', function () {
    $this->as($this->admin);

    visit(route('projects.show', $this->project))
        ->assertPresent('.shared-empty-photos img')
        ->assertPresent('.month-backdrop span[data-today]')
        ->assertSeeIn('.shared-empty-note', 'Nothing shared yet')
        ->assertSeeIn('.shared-empty-note', 'Share something')
        ->assertNoSmoke();
});

it('shows everyone the road to the lottery on a note, growing over itself and back', function (string $name) {
    aUnitType($this->project);
    $this->as(aMemberOf(aFamily($this->project)));

    $page = visit(route($name, $this->project))
        ->assertMissing('.note-sheet')
        ->click('.note-mini')
        ->assertSeeIn('.note-sheet', 'Getting to the lottery')
        ->assertNotPresent('.note-sheet a')
        ->assertPresent('.note-mini[aria-expanded="true"]');

    // Their top right corners together
    expect($page->script('() => {
        const corner = document.querySelector(".note-corner").getBoundingClientRect();
        return Math.abs(document.querySelector(".note-sheet").offsetTop) + Math.abs(document.querySelector(".note-sheet").offsetLeft + document.querySelector(".note-sheet").offsetWidth - corner.width);
    }'))->toBeLessThan(2);

    // A click anywhere else, or Escape, and it goes back
    $page->script('() => document.body.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }))');
    $page->assertMissing('.note-sheet')
        ->click('.note-mini')
        ->assertPresent('.note-sheet')
        ->keys('.note-mini', 'Escape')
        ->assertMissing('.note-sheet')
        ->assertNoSmoke();
})->with(['projects.show', 'lottery.show']);
