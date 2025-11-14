<?php

use App\Models\Admin;
use App\Models\Event;
use App\Models\Member;
use App\Models\Project;

// $eventWithRsvps = Event::with('rsvps')->find(2); // Online meeting with 3 RSVPs

// // Shared data fetches for read-only tests
// $project1 = Project::find(1);
// $admin11 = Admin::find(11);
// $member102 = Member::find(102);
// $member105 = Member::find(105);
// $member106 = Member::find(106);

describe('Event model relations', function () use ($eventWithRsvps, $project1, $admin11) {

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

    it('has member rsvps with correct statuses and timestamps', function () use ($eventWithRsvps) {
        // Test count and instance types
        expect($eventWithRsvps->rsvps)
            ->toHaveCount(3)
            ->each->toBeInstanceOf(Member::class);

        // Test specific RSVP statuses via pivot
        $rsvps = $eventWithRsvps->rsvps->keyBy('id');
        expect($rsvps[102]->pivot->status)->toBe(true);  // accepted
        expect($rsvps[103]->pivot->status)->toBe(true);  // accepted
        expect($rsvps[105]->pivot->status)->toBe(false); // rejected

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

    it('filters events by member rsvp status using scopes', function () use ($member102, $member105) {
        // Member 102 accepted events 2 and 3
        $acknowledgedEvents = Event::scopeAcknowledgedBy(Event::query(), $member102, true)->get();
        expect($acknowledgedEvents->pluck('id'))
            ->toContain(2) // Accepted online meeting
            ->toContain(3) // Accepted past workshop
            ->not->toContain(7); // Different project event

        // Member 105 rejected event 2
        $rejectedEvents = Event::scopeRejectedBy(Event::query(), $member105)->get();
        expect($rejectedEvents->every(function ($event) use ($member105) {
            $rsvp = $event->rsvps->where('id', $member105->id)->first();
            return $rsvp && $rsvp->pivot->status === false;
        }))->toBeTrue();
    });

});

describe('Related model event relations', function () use ($project1, $admin11, $member102, $member105, $member106) {

    it('project has event relations', function () use ($project1) {
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

    it('admin has created event relations', function () use ($admin11) {
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

    it('member has rsvp relations with different statuses', function ($memberId, $expectedCounts, $description) {
        $member = Member::find($memberId);

        // Basic rsvps relation
        expect($member->rsvps)
            ->toHaveCount($expectedCounts['total'])
            ->each->toBeInstanceOf(Event::class);

        // Acknowledged events (any status)
        if ($expectedCounts['acknowledged'] > 0) {
            expect($member->acknowledgedEvents)
                ->toHaveCount($expectedCounts['acknowledged'])
                ->each->pivot->status->not->toBeNull();
        }

        // Accepted events
        if ($expectedCounts['accepted'] > 0) {
            expect($member->acceptedEvents)
                ->toHaveCount($expectedCounts['accepted'])
                ->each->pivot->status->toBe(true);
        }

        // Rejected events
        if ($expectedCounts['rejected'] > 0) {
            expect($member->rejectedEvents)
                ->toHaveCount($expectedCounts['rejected'])
                ->each->pivot->status->toBe(false);
        }

        // Pending events (null status)
        $pendingRsvps = $member->rsvps->filter(fn ($event) => $event->pivot->status === null);
        expect($pendingRsvps)->toHaveCount($expectedCounts['pending']);

    })->with([
        'member with accepted events' => [
            102,
            ['total' => 2, 'acknowledged' => 2, 'accepted' => 2, 'rejected' => 0, 'pending' => 0],
            'Member 102 accepted 2 events'
        ],
        'member with rejected events' => [
            105,
            ['total' => 1, 'acknowledged' => 1, 'accepted' => 0, 'rejected' => 1, 'pending' => 0],
            'Member 105 rejected 1 event'
        ],
        'member with pending rsvp' => [
            106,
            ['total' => 1, 'acknowledged' => 0, 'accepted' => 0, 'rejected' => 0, 'pending' => 1],
            'Member 106 has 1 pending RSVP'
        ]
    ]);

    it('member has upcoming rsvp events filtered correctly', function () use ($member102) {
        $upcomingRsvps = $member102->upcomingRsvps;
        $pastEvent = Event::find(3);

        expect($upcomingRsvps->pluck('id'))
            ->not->toContain($pastEvent->id);
    });

});
