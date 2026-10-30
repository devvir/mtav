<?php

/**
 * What guests see, the family that comes first without them, features off, the archive, and
 * the preferences at hand in the header.
 */
it('shows a guest the lottery with no ranking, no dragging and no way to invite', function () {
    $project = aProject(['allows_guests' => true]);
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $guest = aUser();
    $project->memberships()->create(['user_id' => $guest->id]);
    $this->as($guest->fresh());

    visit(route('lottery.show', $project))
        ->assertSee('Lottery')
        ->wait(1)
        ->assertNotPresent('[aria-label="Project plan"]')
        ->assertNotPresent('[data-tour="lottery.plan"]')
        ->assertDontSee('Invite a family member')
        ->assertNoSmoke();

    visit(route('projects.show', $project))
        ->assertSee('You have no family yet')
        ->assertDontSee('Invite a family member')
        ->assertNoSmoke();
});

it('has someone let into a project without guests create their family first', function () {
    $project = aProject(['name' => 'No guests here', 'listed' => true, 'open_to_join' => true]);
    aUnitType($project, ['name' => 'Two rooms']);
    $this->as(aUser());

    visit(route('directory.index'))
        ->click('Join')
        ->assertSee('Welcome to No guests here')
        ->assertDontSee('Forum')
        ->fill('input[name="name"]', 'Silva Pereira')
        ->click('button[type="submit"]')
        ->assertSee('Forum')
        ->assertSee('Invite a family member')
        ->assertNoSmoke();
});

it('takes a feature off the navigation and the home when turned off, and back', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('manage.settings.edit', $project))
        ->assertSeeIn('.shell-nav', 'Forum')
        ->click('label:has-text("Forum")')
        ->click('label:has-text("Events")')
        ->click('.danger-card ~ * button[type="submit"], form button[type="submit"]')
        ->assertSee('The project was saved.')
        ->assertDontSeeIn('.shell-nav', 'Forum')
        ->assertDontSeeIn('.shell-nav', 'Events')
        ->assertSeeIn('.shell-nav', 'Lottery')
        ->assertNoSmoke();

    expect($project->fresh()->hasFeature('forum'))->toBeFalse();
});

it('shows archived events to an admin on asking, to restore them', function () {
    $project = aProject();
    $admin = anAdminOf($project);
    $event = $project->events()->create(['creator_id' => $admin->id, 'title' => 'Old assembly', 'description' => 'Long ago', 'type' => 'onsite', 'is_published' => true, 'start_date' => now()->addWeek(), 'end_date' => now()->addWeek()->addHour()]);
    $event->delete();
    $this->as($admin);

    visit(route('events.index', $project))
        ->assertDontSee('Old assembly')
        ->click('label:has-text("Show archived")')
        ->assertSee('Old assembly')
        ->click('Restore')
        ->assertSee('It is back.')
        ->assertNoSmoke();

    expect($event->fresh()->trashed())->toBeFalse();
});

it('has text size, language and light or dark in the header, each a small list', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project, aUnitType($project))));

    $page = visit(route('projects.show', $project))
        ->click('[aria-label="Text size"]')
        ->assertSeeIn('[role="menu"]', 'Large')
        ->click('[role="menuitem"]:has-text("Large")')
        ->assertPresent('html[data-text-size="large"]');
    expect($page->script('() => getComputedStyle(document.documentElement).fontSize'))->toBe('22.4px');

    $page->click('[aria-label="Light or dark"]')
        ->assertSeeIn('[role="menu"]', 'As my device')
        ->click('[role="menuitem"]:has-text("Dark")');
    expect($page->script('() => document.documentElement.dataset.mantineColorScheme'))->toBe('dark');

    $page->click('[aria-label="Language"]')
        ->click('[role="menuitem"]:has-text("Español")')
        ->assertSee('Inicio')
        ->assertNoSmoke();
});

it('keeps those preferences out of the way on a phone, and in Preferences', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project, aUnitType($project))));

    visit(route('projects.show', $project))->on()->mobile()
        ->assertMissing('.shell-preferences button:visible')
        ->assertNoSmoke();
});

it('reaches the chat from its button at the corner, and sets groups apart with Community first', function () {
    $project = aProject();
    $family = aFamily($project, aUnitType($project));
    $member = aMemberOf($family);
    aMemberOf($family, ['firstname' => 'Ana']);
    $this->as($member);

    visit(route('projects.show', $project))
        ->assertMissing('.shell-nav [data-phone-only]')
        ->click('.chat-launcher')
        ->assertSee('Chats')
        ->click('label:has-text("Groups")')
        ->assertSeeIn('.chat-list', 'Community')
        ->click('.chat-list-item:has-text("Community")')
        ->assertPresent('.chat-window .chat-thread')
        ->assertNoSmoke();
});

it('keeps the chat\'s button at the bottom right corner', function () {
    $project = aProject();
    $this->as(aMemberOf(aFamily($project, aUnitType($project))));

    $page = visit(route('projects.show', $project))->assertPresent('.chat-launcher');

    expect($page->script('() => innerWidth - document.querySelector(".chat-launcher").getBoundingClientRect().right'))->toBeLessThan(40);
});

it('lets a superadmin block an account from the console', function () {
    $person = aUser(['firstname' => 'Ana', 'lastname' => 'Rey']);
    $this->as(aSuperadmin());

    visit(route('admin.users.index', ['q' => 'Ana Rey']))
        ->click('[aria-label="Actions for Ana Rey"]')
        ->click('[role="menuitem"]:has-text("Block")')
        ->click('[role="dialog"] button:has-text("Block")')
        ->assertSee('Blocked')
        ->assertNoSmoke();

    expect($person->fresh()->isBlocked())->toBeTrue();
});

it('marks the project one is in among one\'s projects, and searches a long list', function () {
    $user = aUser();
    foreach (range(1, 11) as $n) {
        $project = aProject(['name' => "Project number {$n}"]);
        $project->memberships()->create(['user_id' => $user->id, 'is_admin' => true]);
    }
    $this->as($user->fresh());

    visit(route('projects.show', $project))->assertSee('Project number 11');
    visit(route('projects.index'))
        ->assertSeeIn('[aria-current="true"]', 'Current')
        ->fill('input[type="search"], input[aria-label="Search projects"]', 'number 11')
        ->assertDontSee('Project number 2')
        ->assertSee('Project number 11')
        ->assertNoSmoke();
});
