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
        ->click('nav[aria-label="Main"] >> text=Families')
        ->assertPathIs("/projects/{$this->project->id}/families")
        ->assertSee('García')
        ->click('[aria-label="Menu"]')
        ->click('[role="dialog"] >> text=Events')
        ->assertPathIs("/projects/{$this->project->id}/events")
        ->assertNoSmoke();
});

it('opens the admin area\'s navigation in the drawer', function () {
    $this->as($this->admin);

    visit(route('manage.overview', $this->project))
        ->on()->mobile()
        ->click('[aria-label="Menu"]')
        ->click('[role="dialog"] >> text=Unit types')
        ->assertPathIs("/projects/{$this->project->id}/manage/unit_types")
        ->assertSee('Add a unit type')
        ->assertNoSmoke();
});

it('opens modals as sheets that fit the screen', function () {
    $this->as($this->admin);

    $page = visit(route('manage.people.index', $this->project))
        ->on()->mobile()
        ->click("[aria-label=\"Change role or family of {$this->member->name}\"]")
        ->assertSee('Their role and family in this project.');

    expect($page->script('Math.round(document.querySelector(\'[role="dialog"]\').getBoundingClientRect().width) <= window.innerWidth'))->toBeTrue();
});
