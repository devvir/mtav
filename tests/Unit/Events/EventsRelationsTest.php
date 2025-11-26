<?php

use App\Models\Admin;
use App\Models\Event;
use App\Models\Member;
use App\Models\Project;

uses()->group('Unit.Models');

describe('When counting Member invitations to RSVP Events', function () {
    it(
        'correctly counts total, acknowledged, accepted, and declined invitations',
        function (Member $member, array $expectedCounts) {
            $acknowledgedCount = $expectedCounts['accepted'] + $expectedCounts['declined'];

            expect($member->rsvps)
                ->toHaveCount($expectedCounts['total'])
                ->each->toBeInstanceOf(Event::class);

            expect($member->acknowledgedEvents)->toHaveCount($acknowledgedCount);
            expect($member->acceptedEvents)->toHaveCount($expectedCounts['accepted']);
            expect($member->declinedEvents)->toHaveCount($expectedCounts['declined']);

        }
    )->with([
        'Member with 2 accepted Events' => [
            fn () => Member::find(102),
            ['total' => 2, 'accepted' => 2, 'declined' => 0],
        ],
        'Member with 1 accepted and 1 declined Event' => [
            fn () => Member::find(105),
            ['total' => 2, 'accepted' => 1, 'declined' => 1],
        ],
        'Member with 1 pending RSVP' => [
            fn () => Member::find(106),
            ['total' => 1, 'accepted' => 0, 'declined' => 0],
        ]
    ]);
});

describe('PENDING REVIEW / REFACTOR', function () {
    it('belongs to a project', function () {
        $event = Event::find(1); // Lottery event for Project 1

        expect($event->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1)
            ->name->toBe('Project 1');
    });

    it('belongs to a creator admin', function () {
        $event = Event::find(2); // Online meeting created by Admin 11

        expect($event->creator)
            ->toBeInstanceOf(Admin::class)
            ->id->toBe(11)
            ->firstname->toBe('Admin');
    });

    it('has member rsvps with correct statuses and timestamps', function () {
        // Online meeting with 3 RSVPs
        $eventWithRsvps = Event::with('rsvps')->find(2);

        // Test count and instance types
        expect($eventWithRsvps->rsvps)
            ->toHaveCount(3)
            ->each->toBeInstanceOf(Member::class);

        // Test specific RSVP statuses via pivot
        $rsvps = $eventWithRsvps->rsvps->keyBy('id');

        expect($rsvps[102]->pivot->status)->toBe(true);  // accepted
        expect($rsvps[103]->pivot->status)->toBe(true);  // accepted
        expect($rsvps[105]->pivot->status)->toBe(false); // declined

        // Test pivot timestamps
        $firstMember = $eventWithRsvps->rsvps->first();
        expect($firstMember->pivot)
            ->created_at->not->toBeNull()
            ->updated_at->not->toBeNull();
    });

});

describe('Event model scopes', function () {
    it('filters events by publication status', function () {
        $publishedEvents = Event::published()->get();
        $unpublishedEvent = Event::find(5); // Unpublished onsite event

        expect($publishedEvents->pluck('id'))
            ->not->toContain($unpublishedEvent->id);

        expect($publishedEvents->every(fn ($event) => $event->is_published))
            ->toBeTrue();
    });

    it('filters events by date ranges', function ($scopeMethod, $excludeEventId, $dateCheck) {
        $events = Event::{$scopeMethod}()->get();
        $excludedEvent = Event::find($excludeEventId);

        expect($events->pluck('id'))->not->toContain($excludedEvent->id);
        expect($events->every($dateCheck))->toBeTrue();
    })->with([
        'upcoming events' => [
            'upcoming',
            3, // Past workshop
            fn ($event) => $event->end_date > now()
        ],
        'past events' => [
            'past',
            2, // Upcoming online meeting
            fn ($event) => $event->end_date < now()
        ]
    ]);

    it('sorts events with lottery first then by start date', function () {
        $sortedEvents = Event::sorted()->get();

        // First events should be lotteries (multiple projects have lottery events)
        $lotteryCount = Event::where('type', 'lottery')->count();
        $firstEvents = $sortedEvents->take($lotteryCount);
        expect($firstEvents->every(fn ($event) => $event->type->value === 'lottery'))
            ->toBeTrue();

        // Remaining events should be non-lottery
        $nonLotteryEvents = $sortedEvents->skip($lotteryCount);
        if ($nonLotteryEvents->isNotEmpty()) {
            expect($nonLotteryEvents->every(fn ($event) => $event->type->value !== 'lottery'))
                ->toBeTrue();
        }
    });

    it('searches events by various fields', function ($searchTerm, $expectedEventId, $description) {
        $searchResults = Event::search($searchTerm)->get();
        expect($searchResults->pluck('id'))->toContain($expectedEventId);
    })->with([
        'title search'    => ['Community', 2, 'finds "Online Community Meeting"'],
        'location search' => ['Building Street', 4, 'finds events at "123 Building Street"']
    ]);

    it('filters events by member rsvp status using scopes', function () {
        // Member 102 accepted events 2 and 3
        $member102 = Member::find(102);
        $member105 = Member::find(105);

        $acknowledgedEvents = Event::acknowledgedBy($member102, true)->get();
        expect($acknowledgedEvents->pluck('id'))
            ->toContain(2) // Accepted online meeting
            ->toContain(3) // Accepted past workshop
            ->not->toContain(7); // Different project event

        // Member 105 declined event 2
        $declinedEvents = Event::declinedBy($member105)->get();
        expect($declinedEvents->every(function ($event) use ($member105) {
            $rsvp = $event->rsvps->where('id', $member105->id)->first();
            return $rsvp && $rsvp->pivot->status === false;
        }))->toBeTrue();
    });

});

describe('Related model event relations', function () {
    it('project has event relations', function () {
        $project1 = Project::find(1);

        // Test events relation
        expect($project1->events)
            ->toHaveCount(5) // 5 events in Project 1
            ->each->toBeInstanceOf(Event::class);

        // Test upcoming events relation
        $upcomingEvents = $project1->upcomingEvents;
        $pastEvent = Event::find(3);
        expect($upcomingEvents->pluck('id'))
            ->not->toContain($pastEvent->id);

        expect($upcomingEvents->every(fn ($event) => $event->end_date > now()))
            ->toBeTrue();
    });

    it('admin has created event relations', function () {
        $admin11 = Admin::find(11);

        // Test created events relation
        expect($admin11->events)
            ->toHaveCount(5) // Created all 5 events in Project 1
            ->each->toBeInstanceOf(Event::class)
            ->each->creator_id->toBe(11);

        // Test upcoming created events
        $upcomingEvents = $admin11->upcomingEvents;
        $pastEvent = Event::find(3);
        expect($upcomingEvents->pluck('id'))
            ->not->toContain($pastEvent->id);

        expect($upcomingEvents->every(fn ($event) => $event->end_date > now()))
            ->toBeTrue();
    });

    it('member has upcoming rsvp events filtered correctly', function () {
        $member102 = Member::find(102);

        $upcomingRsvps = $member102->upcomingRsvps;
        $pastEvent = Event::find(3);

        expect($upcomingRsvps->pluck('id'))
            ->not->toContain($pastEvent->id);
    });
});
