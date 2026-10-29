<?php

/**
 * On a phone: bottom tabs in the member app, a drawer for the rest of the navigation.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project, null, ['name' => 'García']));
});

it('moves around the member app with the bottom tabs and the drawer', function () {
    $this->as($this->member);

    visit(route('projects.show', $this->project))
        ->on()->mobile()
        ->click('nav[aria-label="Main"] >> text=People')
        ->assertPathIs("/projects/{$this->project->id}/families")
        ->assertSee('García')
        ->click('[aria-label="Menu"]')
        ->click('[role="dialog"] >> text=Events')
        ->assertPathIs("/projects/{$this->project->id}/events")
        ->assertNoSmoke();
});

it('opens the administration group of the sidebar in the drawer', function () {
    $this->as($this->admin);

    visit(route('manage.overview', $this->project))
        ->on()->mobile()
        ->click('[aria-label="Menu"]')
        ->click('[role="dialog"] >> text=Invitations')
        ->assertPathIs("/projects/{$this->project->id}/manage/invitations")
        ->assertSee('Invite someone')
        ->assertNoSmoke();
});

it('opens modals as sheets that fit the screen', function () {
    $this->as($this->admin);

    $page = visit(route('people.show', [$this->project, $this->member]))
        ->on()->mobile()
        ->click('Change role or family')
        ->assertSee('Their role and family in this project.');

    expect($page->script('Math.round(document.querySelector(\'[role="dialog"]\').getBoundingClientRect().width) <= window.innerWidth'))->toBeTrue();
});

it('reads the plan on a phone, full screen and zoomed, but leaves drawing it to larger screens', function () {
    aUnit(aUnitType($this->project), ['identifier' => 'A-101']);
    $this->as($this->admin);

    visit(route('units.index', $this->project))
        ->on()->mobile()
        ->assertPresent('[aria-label="Project plan"]')
        ->assertNotPresent(button('Edit the plan'))
        ->click('[aria-label="Full screen"]')
        ->click('[aria-label="Zoom in"]')
        ->assertPresent('.plan-panel[data-full] [aria-label="Project plan"][data-zoomed]')
        ->assertNoSmoke();
});
