<?php

uses()->group('critical');

/**
 * Info icons explain what needs explaining, and "Tell me more" walks through it.
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
        ->click('Tell me more')
        ->assertSee('Step 1 of 6')
        ->assertSee('Where it stands')
        ->click('Next')
        ->assertSee('Step 2 of 6')
        ->click('Next')
        ->assertSee('On the plan')
        ->click('Next')
        ->assertSee("These are the units of your family's type")
        ->keys('[role="dialog"][aria-modal="true"]', 'Escape')
        ->assertDontSee('Step 4 of 6')
        ->assertNoSmoke();
});

it('walks an admin through running the lottery, and someone in both roles through both', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $admin = anAdminOf($project);

    $this->as($admin);
    openHelp(visit(route('lottery.show', $project))->assertPresent('[data-tour="lottery.schedule"]'), 'How the lottery works')
        ->click('Tell me more')
        ->click('Next')
        ->click('Next')
        ->assertSee('Its date')
        ->assertDontSee('On the plan');

    App\Models\Membership::where('user_id', $admin->id)->update(['family_id' => aFamily($project, $type)->id]);
    $this->as($admin->fresh());
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Tell me more')
        ->assertSee('Step 1 of 9');
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
        ->click('Tell me more')
        ->click('Next')
        ->assertSee('Step 2 of 6')
        ->click('Back')
        ->assertSee('Step 1 of 6')
        ->click('[aria-label="End the tour"]')
        ->assertDontSee('Step 1 of 6');
});

it('finishes a tour on its last step', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    // Once the page is whole (its plan arrives after it), the tip standing still
    openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Tell me more')
        ->click('Next')
        ->click('Next')
        ->click('Next')
        ->click('Next')
        ->click('Next')
        ->assertSee('Step 6 of 6')
        ->click('Done')
        ->assertDontSee('Step 6 of 6');
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

it('stops every tour from any tour, until Preferences turns them back on', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    $page = visit(route('lottery.show', $project));
    $page->script("() => localStorage.setItem('mtav.tours', 'on')");
    $page->refresh()
        ->assertSee('Step 1 of')
        ->click('No more tours')
        ->assertDontSee('Step 1 of')
        ->assertDontSee('Show no more tours?')
        ->assertSee('Preferences turns them back on');

    // Another page's tour, never seen, does not play either
    $page->navigate(route('events.index', $project))->wait(2)->assertDontSee('Step 1 of');

    $page->navigate(route('profile.edit', ['section' => 'preferences']))
        ->click('label:has-text("Guided tours")')
        ->navigate(route('lottery.show', $project))
        ->assertSee('Step 1 of');
});

it('offers each page\'s tour from the (?) by its title', function (string $as, string $name, string $topic, string $first) {
    $project = aProject();
    $people = [
        'member'     => fn () => aMemberOf(aFamily($project, aUnitType($project))),
        'admin'      => fn () => anAdminOf($project),
        'superadmin' => fn () => aSuperadmin(),
    ];
    $this->as($people[$as]());

    $address = str_starts_with($name, 'admin.') || in_array($name, ['notifications.index', 'profile.edit', 'projects.index', 'projects.create', 'directory.index'])
        ? route($name)
        : route($name, $project);

    openHelp(visit($address), $topic)
        ->click('Tell me more')
        ->assertSee('Step 1 of')
        ->assertSee($first)
        ->assertNoSmoke();
})->with([
    'notifications'        => ['member', 'notifications.index', 'Notifications', 'Only what is new'],
    'profile'              => ['member', 'profile.edit', 'Your account', 'Three parts'],
    'your projects'        => ['member', 'projects.index', 'Your projects', 'Open one to go into it'],
    'finding a project'    => ['member', 'directory.index', 'Find a project', 'Search by its name'],
    'starting a project'   => ['member', 'projects.create', 'Starting a project', 'About the project'],
    'project settings'     => ['admin', 'manage.settings.edit', 'Project settings', 'What people see'],
    'inviting your family' => ['member', 'invitations.index', 'Inviting your family', 'Who to invite'],
    'accounts'             => ['superadmin', 'admin.users.index', 'Accounts', 'Find an account'],
    'superadmins'          => ['superadmin', 'admin.superadmins.index', 'Superadmins', 'Who has the role'],
    'platform settings'    => ['superadmin', 'admin.settings.edit', 'Platform settings', 'How long a lottery takes'],
]);
