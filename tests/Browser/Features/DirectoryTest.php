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
        ->assertSee('Join your family')
        ->assertNoSmoke();

    // Every page of the project works for someone who has not joined their family
    foreach (['families.index', 'people.index', 'units.index', 'events.index', 'forum.index', 'media.index', 'lottery.show'] as $name) {
        sameTabTo($page, route($name, $open))->assertNoSmoke();
    }

    sameTabTo($page, route('projects.show', $open))
        ->click('a:has-text("Join your family")')
        ->click('button:has-text("Mine is not here")')
        ->fill('input[name="name"]', 'Silva Pereira')
        ->click('[role="dialog"] button[type="submit"]')
        ->assertDontSee('Join your family')
        ->assertSee('Invite a family member');
});

it('asks to join, withdraws and asks again; an admin declines one and accepts the other from the Invitations tab, whose badge says so', function () {
    $project = aProject(['name' => 'By request', 'listed' => true]);
    aUnitType($project);
    $admin = anAdminOf($project);
    $person = aUser(['firstname' => 'Ana', 'lastname' => 'Rey']);
    $other = aUser(['firstname' => 'Beto', 'lastname' => 'Paz']);

    $this->as($person);
    visit(route('directory.index'))
        ->click('Ask to join')
        ->assertSee('Request sent')
        // Thought better of, then asked again
        ->click('[aria-label="Withdraw the request to By request"]')
        ->assertDontSee('Request sent')
        ->click('Ask to join')
        ->assertSee('Request sent')
        ->assertNoSmoke();

    $this->as($other);
    visit(route('directory.index'))->click('Ask to join')->assertSee('Request sent');

    $this->as($admin);
    visit(route('manage.settings.edit', $project))
        ->assertSeeIn('.settings-tab-count', '2')
        ->click('.settings-tab[href*="invitations"]')
        ->assertSee('Asking to join (2)')
        ->click('.row:has-text("Beto Paz") button:has-text("Decline")')
        ->click(inDialog('Decline'))
        ->assertSee('Asking to join (1)')
        ->click('.row:has-text("Ana Rey") button:has-text("Accept")')
        ->assertDontSee('Asking to join')
        ->assertNoSmoke();

    expect($person->fresh()->canAccess($project))->toBeTrue()
        ->and($other->fresh()->canAccess($project))->toBeFalse();

    // The one declined is told so where they asked
    $this->as($other);
    visit(route('directory.index'))->assertSee('The admins did not accept your request');
});

it('lists a project from its settings, choosing who can join', function () {
    $project = aProject();
    $this->as(anAdminOf($project));

    visit(route('manage.settings.edit', $project))
        ->assertDontSee('Who can join')
        ->click('label:has-text("Show it in Find a project")')
        ->assertSee('Who can join')
        ->click('label:has-text("Anyone, at once")')
        ->click('button[type="submit"]')
        ->assertSee('The project was saved.')
        ->assertNoSmoke();
});

it('names the project each repeated button acts on', function () {
    $open = aProject(['name' => 'Open one', 'listed' => true, 'open_to_join' => true]);
    aUnitType($open);
    $asking = aProject(['name' => 'By request', 'listed' => true]);
    aUnitType($asking);
    $asked = aProject(['name' => 'Asked already', 'listed' => true]);
    aUnitType($asked);
    $mine = aProject(['name' => 'Mine', 'listed' => true]);
    $person = aUser();
    $mine->memberships()->create(['user_id' => $person->id]);
    $asked->joinRequests()->create(['user_id' => $person->id]);
    $inviting = aProject(['name' => 'Inviting']);
    $inviting->invitations()->create([
        'email' => $person->email, 'token_hash' => 'x', 'invited_by' => anAdminOf($inviting)->id, 'expires_at' => now()->addDay(),
    ]);
    $this->as($person);

    visit(route('directory.index'))
        ->assertPresent('button[aria-label="Join Open one"]:has-text("Join")')
        ->assertPresent('button[aria-label="Ask to join By request"]:has-text("Ask to join")')
        ->assertPresent('button[aria-label="Withdraw the request to Asked already"]:has-text("Withdraw")')
        ->assertPresent('a[aria-label="Open Mine"]:has-text("Open")')
        ->assertNoSmoke();

    visit(route('projects.index'))
        ->assertPresent('button[aria-label="Decline the invitation to Inviting"]:has-text("Decline")')
        ->assertPresent('button[aria-label="Join Inviting"]:has-text("Join")')
        ->assertNoSmoke();
});
