<?php

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

    visit(route('manage.events.create', $this->project))
        ->fill('title', 'General assembly')
        ->fill('description', 'Yearly assembly')
        ->fill('location', 'Community hall')
        ->uncheck('is_published')
        ->click('Create the event')
        ->assertSee('The event was created.')
        ->assertSee('Draft');

    $event = Event::firstWhere('title', 'General assembly');

    $this->as($this->member);
    visit(route('events.index', $this->project))->assertDontSee('General assembly');

    $this->as($this->admin);
    visit(route('manage.events.edit', [$this->project, $event]))
        ->check('is_published')
        ->click('Save')
        ->assertSee('The event was saved.');

    $this->as($this->member);
    visit(route('events.index', $this->project))->assertSee('General assembly')->assertNoSmoke();
});

it('asks for a link for online events', function () {
    $this->as($this->admin);

    visit(route('manage.events.create', $this->project))
        ->click('Online')
        ->fill('title', 'Video call')
        ->fill('description', 'Monthly call')
        ->fill('location', 'not a link')
        ->click('Create the event')
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

    visit(route('manage.events.edit', [$this->project, $event]))
        ->click('Cancel the event')
        ->click(inDialog('Cancel the event'))
        ->assertSee('The event was cancelled.');

    expect(Event::find($event->id))->toBeNull();
});
