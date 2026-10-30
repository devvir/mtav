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

it('lets others reply, and shows the topic\'s replies in the list', function () {
    $topic = app(ForumService::class)->open($this->project, $this->ana, 'Assembly date', 'Which Saturday?');
    $this->as($this->beto);

    visit(route('forum.show', [$this->project, $topic]))
        ->fill('body', 'The 14th works for us')
        ->click('Reply')
        ->assertSee('The 14th works for us')
        ->navigate(route('forum.index', $this->project))
        ->assertSee('Assembly date')
        ->assertSee('1 reply');
});

it('lets admins close a topic, after which members cannot reply', function () {
    $topic = app(ForumService::class)->open($this->project, $this->ana, 'Assembly date', 'Which Saturday?');

    $this->as($this->admin);
    visit(route('forum.show', [$this->project, $topic]))->click('Close')->assertSee('Reopen');
    expect(Topic::sole()->is_closed)->toBeTrue();

    $this->as($this->beto->fresh());
    visit(route('forum.show', [$this->project, $topic]))
        ->assertSee('This topic is closed')
        ->assertNotPresent('textarea[name="body"]');
});
