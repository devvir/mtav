<?php

uses()->group('critical');

/**
 * Info icons explain what needs explaining, and "Show me how" walks through it.
 */
it('explains the lottery and walks a member through their ranking', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    aUnit($type, ['identifier' => 'A-102']);
    $member = aMemberOf(aFamily($project, $type));

    $this->as($member);

    // Once the page is whole (its plan arrives after it), the tip standing still
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->assertSee('only ties are drawn at random')
        ->click('Show me how')
        ->assertSee('Step 1 of 4')
        ->assertSee("These are the units of your family's type")
        ->click('Next')
        ->assertSee('Step 2 of 4')
        ->keys('[role="dialog"][aria-modal="true"]', 'Escape')
        ->assertDontSee('Step 2 of 4')
        ->assertNoSmoke();
});

it('closes an explanation once read', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project, aUnitType($project))));

    // Once the page is whole (its plan arrives after it), the tip standing still
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->assertSee('only ties are drawn at random')
        ->click('Got it')
        ->assertDontSee('only ties are drawn at random');
});

it('goes back and forth through a tour, and ends it at any step', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    // Once the page is whole (its plan arrives after it), the tip standing still
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Show me how')
        ->click('Next')
        ->assertSee('Step 2 of 4')
        ->click('Back')
        ->assertSee('Step 1 of 4')
        ->click('[aria-label="End the tour"]')
        ->assertDontSee('Step 1 of 4');
});

it('finishes a tour on its last step', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    // Once the page is whole (its plan arrives after it), the tip standing still
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Show me how')
        ->click('Next')
        ->click('Next')
        ->click('Next')
        ->assertSee('Step 4 of 4')
        ->click('Done')
        ->assertDontSee('Step 4 of 4');
});

it('plays a page\'s tour by itself the first time, and offers once to show no more', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    $page = visit(route('lottery.show', $project));
    $page->script("() => localStorage.setItem('mtav.tours', 'on')");
    $page->refresh()
        ->assertSee('Step 1 of')
        ->click('[aria-label="End the tour"]')
        ->assertSee('Show no more tours?')
        ->click('No more tours')
        ->assertDontSee('Show no more tours?');

    // Seen, and no more asked for: it does not come back
    $page->refresh()->wait(2)->assertDontSee('Step 1 of');
});

it('offers each page\'s tour from the (?) by its title', function (string $as, string $name, string $topic, string $first) {
    $project = aProject();
    $people = [
        'member' => fn () => aMemberOf(aFamily($project, aUnitType($project))),
        'admin' => fn () => anAdminOf($project),
        'superadmin' => fn () => aSuperadmin(),
    ];
    $this->as($people[$as]());

    $address = str_starts_with($name, 'admin.') || in_array($name, ['notifications.index', 'profile.edit', 'projects.index', 'projects.create'])
        ? route($name)
        : route($name, $project);

    openHelp(visit($address), $topic)
        ->click('Show me how')
        ->assertSee('Step 1 of')
        ->assertSee($first)
        ->assertNoSmoke();
})->with([
    'notifications' => ['member', 'notifications.index', 'Notifications', 'Only what is new'],
    'profile' => ['member', 'profile.edit', 'Your account', 'Three parts'],
    'your projects' => ['member', 'projects.index', 'Your projects', 'Open one to go into it'],
    'starting a project' => ['member', 'projects.create', 'Starting a project', 'About the project'],
    'project settings' => ['admin', 'manage.settings.edit', 'Project settings', 'What people see'],
    'inviting your family' => ['member', 'invitations.index', 'Inviting your family', 'Who to invite'],
    'accounts' => ['superadmin', 'admin.users.index', 'Accounts', 'Find an account'],
    'superadmins' => ['superadmin', 'admin.superadmins.index', 'Superadmins', 'Who has the role'],
    'platform settings' => ['superadmin', 'admin.settings.edit', 'Platform settings', 'How long a lottery takes'],
]);
