<?php

/**
 * Finding a project, joining it, and getting a family once in.
 */
it('finds a project from the selector, joins it, creates a family, and sees everything without one', function () {
    $mine = aProject(['name' => 'Mine']);
    aMemberOf(aFamily($mine, aUnitType($mine)), ['email' => 'person@example.com']);
    $open = aProject(['name' => 'Open one', 'listed' => true, 'open_to_join' => true, 'allows_guests' => true]);
    aUnit($type = aUnitType($open, ['name' => 'Two rooms']));
    $person = App\Models\User::firstWhere('email', 'person@example.com');
    $this->as($person);

    $page = visit(route('projects.show', $mine))
        ->click('.shell-switcher')
        ->click('Find a project')
        ->assertSee('Open one')
        ->click('Join')
        ->assertSee('You have no family yet')
        ->assertNoSmoke();

    // Every page of the project works for someone with no family
    foreach (['families.index', 'people.index', 'units.index', 'events.index', 'forum.index', 'media.index', 'lottery.show'] as $name) {
        $page->navigate(route($name, $open))->assertNoSmoke();
    }

    $page->navigate(route('projects.show', $open))
        ->click('Create our family')
        ->fill('input[name="name"]', 'Silva Pereira')
        ->click('[role="dialog"] button[type="submit"]')
        ->assertDontSee('You have no family yet')
        ->assertSee('Invite a family member');
});

it('asks to join, and an admin accepts from the Invitations tab, whose badge says so', function () {
    $project = aProject(['name' => 'By request', 'listed' => true]);
    aUnitType($project);
    $admin = anAdminOf($project);
    $person = aUser(['firstname' => 'Ana', 'lastname' => 'Rey']);

    $this->as($person);
    visit(route('directory.index'))
        ->click('Ask to join')
        ->assertSee('Request sent')
        ->assertNoSmoke();

    $this->as($admin);
    visit(route('manage.settings.edit', $project))
        ->assertSeeIn('.settings-tab-count', '1')
        ->click('.settings-tab[href*="invitations"]')
        ->assertSee('Asking to join (1)')
        ->assertSee('Ana Rey')
        ->click('Accept')
        ->assertDontSee('Asking to join')
        ->assertNoSmoke();

    expect($person->fresh()->canAccess($project))->toBeTrue();
});

it('lists a project from its settings, choosing who can join', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('manage.settings.edit', $project))
        ->assertDontSee('Who can join')
        ->click('label:has-text("List it in the directory")')
        ->assertSee('Who can join')
        ->click('label:has-text("Anyone, at once")')
        ->click('button[type="submit"]')
        ->assertSee('The project was saved.')
        ->assertNoSmoke();
});
