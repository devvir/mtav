<?php

use App\Models\Admin;
use App\Models\Event;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;

describe('User Model', function () {
    it('can be converted to a Member when is_admin is false', function () {
        $user = User::find(102); // Member #102 from universe

        expect($user->asMember())
            ->toBeInstanceOf(Member::class)
            ->firstname->toBe($user->firstname);
    });

    it('returns null when converting to Member if is_admin is true', function () {
        $user = User::find(11); // Admin #11 from universe

        expect($user->asMember())->toBeNull();
    });

    it('can be converted to an Admin when is_admin is true', function () {
        $user = User::find(11); // Admin #11 from universe

        expect($user->asAdmin())
            ->toBeInstanceOf(Admin::class)
            ->firstname->toBe($user->firstname);
    });

    it('returns null when converting to Admin if is_admin is false', function () {
        $user = User::find(102); // Member #102 from universe

        expect($user->asAdmin())->toBeNull();
    });

    it('identifies members correctly', function () {
        $member = User::find(102); // Member #102 from universe
        $admin = User::find(11); // Admin #11 from universe

        expect($member->isMember())->toBeTrue()
            ->and($member->isAdmin())->toBeFalse()
            ->and($admin->isMember())->toBeFalse()
            ->and($admin->isAdmin())->toBeTrue();
    });

    it('identifies superadmins based on config', function () {
        config(['auth.superadmins' => ['superadmin1@example.com']]);

        $superadmin = User::find(1); // Superadmin #1 from universe
        $regularAdmin = User::find(11); // Admin #11 from universe
        $member = User::find(102); // Member #102 from universe

        expect($superadmin->isSuperadmin())->toBeTrue()
            ->and($regularAdmin->isSuperadmin())->toBeFalse()
            ->and($member->isSuperadmin())->toBeFalse();
    });

    it('has projects relationship that returns only active projects', function () {
        // Factory: Need fresh user and projects with controlled active/inactive state
        // Fixture projects have complex assignments; this tests the active filtering logic
        $user = User::factory()->create();
        $project1 = Project::factory()->create();
        $project2 = Project::factory()->create();
        $project3 = Project::factory()->create();

        // Attach with active=true
        $user->projects()->attach($project1, ['active' => true]);
        $user->projects()->attach($project2, ['active' => true]);
        // Attach with active=false
        $user->projects()->attach($project3, ['active' => false]);

        expect($user->projects)->toHaveCount(2)
            ->and($user->projects->contains($project1))->toBeTrue()
            ->and($user->projects->contains($project2))->toBeTrue()
            ->and($user->projects->contains($project3))->toBeFalse();
    });
});

describe('Member Model (User is_admin=false)', function () {
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
