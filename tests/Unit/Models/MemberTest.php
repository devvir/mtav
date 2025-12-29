<?php

use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

describe('Member Model Relations', function () {
    it('belongs to a family', function () {
        $member = Member::find(102); // Member in Family #4

        expect($member->family)
            ->toBeInstanceOf(Family::class)
            ->id->toBe(4);
    });

    it('belongs to a project through family', function () {
        $member = Member::find(102); // Member in Project 1

        expect($member->projects()->first())
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1);
    });

    it('has published events for their project', function () {
        $member = Member::find(102); // Member in Project 1

        $events = $member->events;
        expect($events->every(fn ($e) => $e->is_published === true))->toBeTrue();
        expect($events->every(fn ($e) => $e->project_id === $member->projects()->latest()->first()->id))->toBeTrue();
    });

    it('has upcoming published events for their project', function () {
        $member = Member::find(102);

        $upcomingEvents = $member->upcomingEvents;
        expect($upcomingEvents->every(function ($event) {
            return is_null($event->start_date) || $event->start_date > now();
        }))->toBeTrue();
    });
});

describe('Member Model - RSVP Functionality', function () {
    it('correctly counts member rsvp invitations by status', function (Member $member, array $expectedCounts) {
        expect($member->rsvps)
            ->toHaveCount($expectedCounts['total'])
            ->each->toBeInstanceOf(Event::class);

        expect($member->acknowledgedEvents)->toHaveCount(
            $expectedCounts['accepted'] + $expectedCounts['declined']
        );
        expect($member->acceptedEvents)->toHaveCount($expectedCounts['accepted']);
        expect($member->declinedEvents)->toHaveCount($expectedCounts['declined']);

    })->with([
        'Member with 2 accepted events' => [
            fn () => Member::find(102),
            ['total' => 2, 'accepted' => 2, 'declined' => 0],
        ],
        'Member with 1 accepted and 1 declined event' => [
            fn () => Member::find(105),
            ['total' => 2, 'accepted' => 1, 'declined' => 1],
        ],
        'Member with 1 pending RSVP' => [
            fn () => Member::find(106),
            ['total' => 1, 'accepted' => 0, 'declined' => 0],
        ]
    ]);

    it('has upcoming rsvp invitations', function () {
        $member = Member::find(102);

        $upcomingRsvps = $member->upcomingRsvps;
        expect($upcomingRsvps->every(function ($event) {
            return is_null($event->start_date) || $event->start_date > now();
        }))->toBeTrue();
    });

    it('can rsvp to an event', function () {
        $member = Member::find(100);
        $event = Event::find(2);

        // Initial state - member not invited
        expect($member->rsvps->pluck('id'))->not->toContain($event->id);

        // Accept event
        $member->rsvp($event, true);
        $member->refresh();

        expect($member->acceptedEvents->pluck('id'))->toContain($event->id);

        // Decline event
        $member->rsvp($event, false);
        $member->refresh();

        expect($member->declinedEvents->pluck('id'))->toContain($event->id);
        expect($member->acceptedEvents->pluck('id'))->not->toContain($event->id);
    });

    it('stores and retrieves rsvp status from pivot', function () {
        $event = Event::find(2);
        $member102 = $event->rsvps->where('id', 102)->first();
        $member105 = $event->rsvps->where('id', 105)->first();

        expect($member102->pivot->status)->toBe(1); // Accepted (stored as 1)
        expect($member105->pivot->status)->toBe(0); // Declined (stored as 0)
    });

    it('records pivot timestamps on rsvp', function () {
        $event = Event::find(2);
        $member = $event->rsvps->first();

        expect($member->pivot)
            ->created_at->not->toBeNull()
            ->updated_at->not->toBeNull();
    });
});

describe('Member Model - Search and Scopes', function () {
    it('can search by family name', function () {
        $member = Member::find(102); // Member in Family #4

        $results = Member::search($member->family->name, searchFamily: true)->get();

        expect($results->pluck('id'))->toContain(102);
    });
});
