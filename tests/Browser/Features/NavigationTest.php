<?php

/**
 * Getting around: the account menu, the project switcher, the home page's shortcuts,
 * and what an address that leads nowhere shows.
 */

beforeEach(function () {
    $this->project = aProject(['name' => 'Cooperativa Uno']);
    $this->member = aMemberOf(aFamily($this->project));
});

/** The path of a named route, as the browser shows it */
function pathOf(string $name, mixed $parameters = []): string
{
    return route($name, $parameters, absolute: false);
}

it('opens the guide and the questions and answers from the account menu', function () {
    $this->as($this->member);

    visit(route('projects.show', $this->project))
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Guide')
        ->assertPathIs(pathOf('documentation.guide'))
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Questions and answers')
        ->assertPathIs(pathOf('documentation.faq'))
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Profile')
        ->assertPathIs(pathOf('profile.edit'));
});

it('offers superadmins the platform console, and no one else', function () {
    $this->as($this->member);
    visit(route('projects.show', $this->project))
        ->click('[aria-label="Your account"]')
        ->assertDontSeeIn('[role="menu"]', 'Platform console');

    $this->as(aSuperadmin());
    visit(route('projects.index'))
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Platform console')
        ->assertPathIs(pathOf('admin.projects.index'));
});

it('lists all of someone\'s projects from the switcher and the account menu', function () {
    $second = aProject(['name' => 'Cooperativa Dos']);
    $this->as(anAdminOf($this->project, $second));

    visit(route('projects.show', $this->project))
        ->click('header button:has-text("Cooperativa Uno")')
        ->click('[role="menu"] >> text=All your projects')
        ->assertPathIs(pathOf('projects.index'))
        ->assertSee('Cooperativa Dos')
        ->click('[aria-label="Your account"]')
        ->click('[role="menu"] >> text=Your projects')
        ->assertPathIs(pathOf('projects.index'));
});

it('leads from home to every event and every file', function () {
    $this->as($this->member);

    visit(route('projects.show', $this->project))
        ->click('All events')
        ->assertPathIs(pathOf('events.index', $this->project));

    visit(route('projects.show', $this->project))
        ->click('All files')
        ->assertPathIs(pathOf('media.index', $this->project));
});

it('says when a page does not exist, and leads back to the start', function () {
    $this->as($this->member);

    visit('/no-such-page')
        ->assertSee('Page not found')
        ->click('Go to the start')
        ->assertPathIs(pathOf('projects.show', $this->project));
});

it('links someone\'s details to their family\'s', function () {
    $this->as($this->member);
    $family = $this->member->familyIn($this->project);

    visit(route('people.show', [$this->project, $this->member]))
        ->click("a:has-text(\"{$family->name} family\")")
        ->assertPathIs(pathOf('families.index', $this->project))
        ->assertQueryStringHas('family', (string) $family->id)
        ->assertSeeIn('[role="dialog"]', $family->name);
});
