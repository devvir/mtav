<?php

use App\Models\Topic;
use App\Services\ForumService;

/**
 * The forum, as people use it: opening a topic, replying, an admin closing it.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->ana = aMemberOf(aFamily($this->project), ['firstname' => 'Ana']);
    $this->beto = aMemberOf(aFamily($this->project), ['firstname' => 'Beto']);
});

it('lets someone open a topic from the forum, and lands them in it', function () {
    $this->as($this->ana);

    visit(route('forum.index', $this->project))
        ->assertSee('No topics yet')
        ->click('Open a topic')
        ->fill('title', 'Assembly date')
        ->fill('body', 'Which Saturday suits everyone?')
        ->click(inDialog('Open the topic'))
        ->assertSee('Your topic is open.')
        ->assertSee('Which Saturday suits everyone?')
        ->assertNoSmoke();
});

it('lets others reply, edit and remove their reply, and shows the topic\'s replies in the list', function () {
    $topic = app(ForumService::class)->open($this->project, $this->ana, 'Assembly date', 'Which Saturday?');
    $this->as($this->beto);

    $page = visit(route('forum.show', [$this->project, $topic]))
        ->fill('body', 'The 14th works for us')
        ->click('Reply')
        ->assertSee('The 14th works for us')
        // A second thought, written into the same reply
        ->click('[aria-label="Post options"]')
        ->click('[role="menuitem"]:has-text("Edit")')
        ->fill('.forum-post textarea[aria-label="Message"]', 'The 21st works better for us')
        ->click('.forum-post button:has-text("Save")')
        ->assertSee('The 21st works better for us')
        ->assertSee('edited')
        ->click('.shell-nav a:has-text("Forum")')
        ->assertSee('Assembly date')
        ->assertSee('1 reply')
        ->assertNoSmoke();

    // Removed after all: the list counts none, though it was loaded ahead of the removal, on
    // pointing at its link (the page reloaded first, so that nothing has it yet)
    $list = route('forum.index', $this->project, absolute: false);
    $page->click('a:has-text("Assembly date")')->assertSee('The 21st works better for us');
    answered(watchRequests($page->refresh()->assertSee('The 21st works better for us'))->hover('.shell-nav a:has-text("Forum")'), $list)
        ->click('[aria-label="Post options"]')
        ->click('[role="menuitem"]:has-text("Remove")')
        ->click(inDialog('Remove'))
        ->assertDontSee('The 21st works better for us')
        ->click('.shell-nav a:has-text("Forum")');
    waitUntil($page, 'location.pathname === ' . json_encode($list))
        ->assertSee('Assembly date')
        ->assertDontSee('1 reply');
});

it('lets admins close a topic, after which members cannot reply, and remove it', function () {
    $topic = app(ForumService::class)->open($this->project, $this->ana, 'Assembly date', 'Which Saturday?');

    $this->as($this->admin);
    visit(route('forum.show', [$this->project, $topic]))->click('Close')->assertSee('Reopen');
    expect(Topic::sole()->is_closed)->toBeTrue();

    $this->as($this->beto->fresh());
    visit(route('forum.show', [$this->project, $topic]))
        ->assertSee('This topic is closed')
        ->assertNotPresent('textarea[name="body"]');

    // An admin removes it, with everything in it
    $this->as($this->admin);
    visit(route('forum.show', [$this->project, $topic]))
        ->click('[aria-label="Remove the topic"]')
        ->click(inDialog('Remove'))
        ->assertPathIs(route('forum.index', $this->project, absolute: false))
        ->assertDontSee('Assembly date')
        ->assertNoSmoke();
    expect(Topic::count())->toBe(0);
});
