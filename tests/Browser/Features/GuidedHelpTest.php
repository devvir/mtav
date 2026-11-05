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

it('keeps the focus inside a step, and gives it back where it was when the tour ends', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));
    $inCard = '() => document.activeElement.closest(".tour-card") !== null';

    $page = openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Tell me more')
        ->assertSee('Step 1 of');

    // Past "Next", and on around: never out to the dimmed page
    foreach (range(1, 6) as $_) {
        $page->keys(':focus', 'Tab');
        expect($page->script($inCard))->toBeTrue();
    }
    $page->keys(':focus', 'Shift+Tab');
    expect($page->script($inCard))->toBeTrue();

    $page->keys(':focus', 'Escape')->assertMissing('.tour-card');
    expect($page->script('() => document.activeElement.getAttribute("aria-label")'))->toBe('About: How the lottery works');
});

it('leaves the question after a tour for whoever wants it, without taking the focus', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    $page = openHelp(visit(route('lottery.show', $project))->assertPresent('[aria-label="Project plan"]'), 'How the lottery works')
        ->click('Tell me more')
        ->assertSee('Step 1 of')
        ->keys(':focus', 'Escape')
        ->assertSee('Show no more tours?')
        ->assertPresent('.tour-offer[role="status"]');

    expect($page->script('() => document.activeElement.closest(".tour-offer") === null'))->toBeTrue();
});

/** Every step of the tour playing, by its title, to its end */
function tourTitles(object $page): array
{
    $titles = [];
    while (count($titles) < 20) {
        $page->assertSee('Step ' . (count($titles) + 1) . ' of');
        $titles[] = $page->script("() => document.querySelector('.tour-title')?.textContent");
        if ($page->script("() => [...document.querySelectorAll('.tour-card button')].some((button) => button.textContent === 'Done')")) {
            break;
        }
        $page->click('.tour-card button:has-text("Next")');
    }

    return $titles;
}

it('walks through the whole home page, then the ways out of it', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    $page = openHelp(visit(route('projects.show', $project))->resize(1440, 900)->assertSee('The people of the project'), 'The project at a glance')
        ->click('Tell me more');

    // Until the project is set up, how far it is comes first, for everyone
    expect(tourTitles($page))->toBe([
        'Getting to the lottery', 'The project in figures', 'Recently shared', 'Coming up', 'The people of the project', 'The units',
        'Everything else', 'Quick actions', 'What is new', 'Messages',
    ]);
    $page->assertNoSmoke();
});

it('leaves out on a phone what a phone does not show, and keeps each step clear of what it points at', function () {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $this->as(aMemberOf(aFamily($project, $type)));

    $page = openHelp(visit(route('projects.show', $project))->on()->mobile()->assertSee('The people of the project'), 'The project at a glance')
        ->click('Tell me more');
    $clear = fn () => $page->script(<<<'JS'
        () => {
            const card = document.querySelector('.tour-card').getBoundingClientRect();
            const lit = document.querySelector('.tour-spotlight').getBoundingClientRect();
            return card.bottom <= lit.top || card.top >= lit.bottom || lit.height > innerHeight / 2;
        }
        JS);

    $titles = [];
    while (count($titles) < 20) {
        $page->wait(0.8);
        $titles[] = $page->script("() => document.querySelector('.tour-title')?.textContent");
        expect($clear())->toBeTrue();
        if ($page->script("() => [...document.querySelectorAll('.tour-card button')].some((button) => button.textContent === 'Done')")) {
            break;
        }
        $page->click('.tour-card button:has-text("Next")');
    }

    // The tabs at the bottom are the way around; the chat is one of them, not a bubble
    expect($titles)->toContain('Everything else')->not->toContain('Messages');
});

it('plays each page\'s tour on a phone, every step clear of what it points at', function (string $as, string $name, string $topic) {
    $project = aProject();
    $type = aUnitType($project);
    aUnit($type, ['identifier' => 'A-101']);
    $people = [
        'member' => fn () => aMemberOf(aFamily($project, $type)),
        'admin'  => fn () => anAdminOf($project),
    ];
    $this->as($people[$as]());

    // Once the page has settled at its phone size
    $page = openHelp(visit(route($name, $project))->on()->mobile()->wait(1.5), $topic)->click('Tell me more');
    $steps = 0;
    while ($steps++ < 20) {
        $page->wait(0.8);
        expect($page->script(<<<'JS'
            () => {
                const card = document.querySelector('.tour-card').getBoundingClientRect();
                const lit = document.querySelector('.tour-spotlight')?.getBoundingClientRect();
                const inside = card.left >= 0 && card.right <= innerWidth && card.top >= 0 && card.bottom <= innerHeight;
                return inside && (!lit || card.bottom <= lit.top || card.top >= lit.bottom || lit.height > innerHeight / 2);
            }
            JS))->toBeTrue();
        if ($page->script("() => [...document.querySelectorAll('.tour-card button')].some((button) => button.textContent === 'Done')")) {
            break;
        }
        $page->click('.tour-card button:has-text("Next")');
    }
    $page->assertNoSmoke();
})->with([
    'lottery'     => ['member', 'lottery.show', 'How the lottery works'],
    'families'    => ['member', 'families.index', 'What is a family?'],
    'people'      => ['member', 'people.index', 'Everyone in the project'],
    'units'       => ['admin', 'units.index', 'What is a unit type?'],
    'events'      => ['member', 'events.index', 'About events'],
    'files'       => ['member', 'media.index', 'About files'],
    'forum'       => ['member', 'forum.index', 'The forum'],
    'chat'        => ['member', 'chat.index', 'Messages'],
    'setup'       => ['admin', 'manage.overview', 'Setting up the project'],
    'invitations' => ['admin', 'manage.invitations.index', 'How invitations work'],
    'settings'    => ['admin', 'manage.settings.edit', 'Project settings'],
]);
