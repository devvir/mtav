<?php

use App\Enums\EventType;
use App\Models\Event;

/**
 * Events: drafts, publishing, attendance.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project), ['firstname' => 'Ana', 'lastname' => 'García']);
});

it('keeps a draft from members until it is published', function () {
    $this->as($this->admin);

    visit(route('events.index', $this->project))
        ->click('Create an event')
        ->fill('title', 'General assembly')
        ->fill('description', 'Yearly assembly')
        ->fill('location', 'Community hall')
        ->uncheck('is_published')
        ->click(inDialog('Create the event'))
        ->assertSee('The event was created.')
        ->navigate(route('events.index', $this->project))
        ->assertSee('Draft');

    $event = Event::firstWhere('title', 'General assembly');

    $this->as($this->member);
    visit(route('events.index', $this->project))->assertDontSee('General assembly');

    $this->as($this->admin);
    visit(route('events.show', [$this->project, $event]))
        ->click('Edit')
        ->check('is_published')
        ->click(inDialog('Save'))
        ->assertSee('The event was saved.');

    $this->as($this->member);
    visit(route('events.index', $this->project))->assertSee('General assembly')->assertNoSmoke();
});

it('shows members no tools to create or change events', function () {
    $event = anEvent($this->project, ['title' => 'Assembly']);
    $this->as($this->member);

    visit(route('events.index', [$this->project, 'new' => 1]))
        ->assertDontSee('Create an event')
        ->assertNotPresent('[role="dialog"]')
        ->navigate(route('events.show', [$this->project, $event]))
        ->assertSee('Assembly')
        ->assertDontSee('Cancel the event');
});

it('asks for a link for online events', function () {
    $this->as($this->admin);

    visit(route('events.index', [$this->project, 'new' => 1]))
        ->click('Online')
        ->fill('title', 'Video call')
        ->fill('description', 'Monthly call')
        ->fill('location', 'not a link')
        ->click(inDialog('Create the event'))
        ->assertSee('The location field must be a valid URL.');
});

it('lets members say whether they go, and shows who goes', function () {
    $event = anEvent($this->project, ['title' => 'Assembly', 'allows_rsvp' => true]);
    $this->as($this->member);

    visit(route('events.show', [$this->project, $event]))
        ->assertSee('Will you go?')
        ->click('Yes')
        ->assertSee('Great, see you there!')
        ->assertSee('Going (1)')
        ->assertSee('Ana García')
        ->click('No')
        ->assertSee('Going (0)');

    expect($event->rsvps()->first()->pivot->attending)->toBe(0);
});

it('cancels an event', function () {
    $event = anEvent($this->project, ['title' => 'Picnic']);
    $this->as($this->admin);

    visit(route('events.show', [$this->project, $event]))
        ->click('Cancel the event')
        ->click(inDialog('Cancel the event'))
        ->assertSee('The event was cancelled.');

    expect(Event::find($event->id))->toBeNull();
});

it('asks members whether they go only when the event says so', function () {
    $this->as($this->admin);

    visit(route('events.index', $this->project))
        ->click('Create an event')
        ->fill('title', 'Work day')
        ->fill('description', 'Painting the fences')
        ->fill('location', 'The site')
        ->click('Ask people whether they will go')
        ->click(inDialog('Create the event'))
        ->assertSee('The event was created.');

    anEvent($this->project, ['title' => 'Open day']);

    $this->as($this->member);
    visit(route('events.show', [$this->project, Event::firstWhere('title', 'Work day')]))
        ->assertSee('Will you go?');
    visit(route('events.show', [$this->project, Event::firstWhere('title', 'Open day')]))
        ->assertSee('Open day')
        ->assertDontSee('Will you go?');
});

it('lets members join an online event from its page', function () {
    $event = anEvent($this->project, [
        'title'    => 'Monthly call',
        'type'     => EventType::ONLINE,
        'location' => 'https://meet.example.com/mtav',
    ]);
    $this->as($this->member);

    visit(route('events.show', [$this->project, $event]))
        ->assertAttribute('a:has-text("Join online")', 'href', 'https://meet.example.com/mtav');
});
