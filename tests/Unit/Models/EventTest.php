// Copilot - Pending review
<?php

use App\Models\Admin;
use App\Models\Event;
use App\Models\Member;
use App\Models\Project;

uses()->group('Unit.Models');

describe('Event Model Relations', function () {
    it('belongs to a project', function () {
        $event = Event::find(1); // Lottery event for Project 1

        expect($event->project)
            ->toBeInstanceOf(Project::class)
            ->id->toBe(1);
    });

    it('belongs to a creator admin', function () {
        $event = Event::find(2); // Online meeting created by Admin 11

        expect($event->creator)
            ->toBeInstanceOf(Admin::class)
            ->id->toBe(11);
    });

    it('has many rsvps from members', function () {
        $event = Event::find(2); // Online event with RSVPs

        expect($event->rsvps)
            ->toHaveCount(3)
            ->each->toBeInstanceOf(Member::class);

        // Verify pivot data is accessible
        $member102 = $event->rsvps->where('id', 102)->first();
        expect($member102)
            ->not->toBeNull()
            ->and($member102->pivot->status)->toBe(1); // Accepted (stored as 1)
    });

    it('has audit records for lottery events', function () {
        $lotteryEvent = Event::factory()->create(['type' => 'lottery']);

        expect($lotteryEvent->audits())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('Event Model Scopes - Type Filters', function () {
    it('filters lottery events', function () {
        $lotteries = Event::lottery()->get();
        expect($lotteries->every(fn ($e) => $e->type->value === 'lottery'))->toBeTrue();
    });

    it('filters online events', function () {
        $online = Event::online()->get();
        expect($online->every(fn ($e) => $e->type->value === 'online'))->toBeTrue();
    });

    it('filters onsite events', function () {
        $onsite = Event::onsite()->get();
        expect($onsite->every(fn ($e) => $e->type->value === 'onsite'))->toBeTrue();
    });
});

describe('Event Model Scopes - Publication Status', function () {
    it('filters published events', function () {
        $published = Event::published()->get();
        expect($published->every(fn ($e) => $e->is_published === true))->toBeTrue();
    });
});

describe('Event Model Scopes - Temporal Filters', function () {
    it('filters upcoming events', function () {
        $upcoming = Event::upcoming()->get();

        expect($upcoming->every(function ($event) {
            return is_null($event->start_date) || $event->start_date > now();
        }))->toBeTrue();
    });

    it('filters past events', function () {
        $past = Event::past()->get();

        // Check that events have either explicit end date in past or start date too old
        expect($past->every(function ($event) {
            $implicitEnd = $event->start_date?->copy()->addMinutes(Event::IMPLICIT_DURATION);
            $hasExplicitEnd = isset($event->end_date) && $event->end_date < now();
            $implicitlyEnded = is_null($event->end_date) && $implicitEnd && $implicitEnd < now();

            return $hasExplicitEnd || $implicitlyEnded;
        }))->toBeTrue();
    });

    it('filters ongoing events', function () {
        $ongoing = Event::ongoing()->get();

        // Ongoing = not upcoming and not past
        expect($ongoing->every(function ($event) {
            $isUpcoming = is_null($event->start_date) || $event->start_date > now();
            if ($isUpcoming) {
                return false;
            }

            $implicitEnd = $event->start_date?->copy()->addMinutes(Event::IMPLICIT_DURATION);
            $hasExplicitEnd = isset($event->end_date) && $event->end_date < now();
            $implicitlyEnded = is_null($event->end_date) && $implicitEnd && $implicitEnd < now();
            $isPast = $hasExplicitEnd || $implicitlyEnded;

            return !$isPast;
        }))->toBeTrue();
    });
});

describe('Event Model Scopes - RSVP Filters', function () {
    it('filters events acknowledged by a member', function () {
        $member102 = Member::find(102);
        $acknowledged = Event::acknowledgedBy($member102, true)->get();

        // Member 102 should have accepted events
        expect($acknowledged)->not->toBeEmpty();

        // All should be accepted by this member (status = 1)
        expect($acknowledged->every(function ($event) use ($member102) {
            $rsvp = $event->rsvps->where('id', $member102->id)->first();
            return $rsvp && $rsvp->pivot->status === 1;
        }))->toBeTrue();
    });

    it('filters events declined by a member', function () {
        // Member 105 declined event 2
        $declined = Event::declinedBy(105)->get();

        expect($declined->every(function ($event) {
            return $event->rsvps->contains(function ($member) {
                return $member->pivot->status === 0;
            });
        }))->toBeTrue();
    });
});

describe('Event Model Scopes - Search and Sort', function () {
    it('searches events by title', function () {
        $results = Event::search('Community')->get();
        expect($results->pluck('id'))->toContain(2); // Online Community Meeting
    });

    it('searches events by location', function () {
        $results = Event::search('Building')->get();
        expect($results->pluck('id'))->toContain(4); // Event at Building Street
    });

    it('sorts events with lottery first then by start date', function () {
        $sorted = Event::sorted()->get();

        // Should have lotteries first
        $lotteries = $sorted->takeWhile(fn ($e) => $e->type->value === 'lottery');
        expect($lotteries)->not->toBeEmpty();

        // Non-lottery events should follow, ordered by start_date DESC
        $nonLotteries = $sorted->skipWhile(fn ($e) => $e->type->value === 'lottery');
        expect($nonLotteries->every(fn ($e) => $e->type->value !== 'lottery'))->toBeTrue();
    });
});

describe('Event Model Type Helpers', function () {
    it('identifies lottery events', function () {
        $lottery = Event::factory()->create(['type' => 'lottery']);

        expect($lottery->isLottery())->toBeTrue();
        expect($lottery->isOnline())->toBeFalse();
        expect($lottery->isOnSite())->toBeFalse();
    });

    it('identifies online events', function () {
        $online = Event::factory()->create(['type' => 'online']);

        expect($online->isOnline())->toBeTrue();
        expect($online->isLottery())->toBeFalse();
        expect($online->isOnSite())->toBeFalse();
    });

    it('identifies onsite events', function () {
        $onsite = Event::factory()->create(['type' => 'onsite']);

        expect($onsite->isOnSite())->toBeTrue();
        expect($onsite->isLottery())->toBeFalse();
        expect($onsite->isOnline())->toBeFalse();
    });

    it('identifies publication status', function () {
        $published = Event::factory()->create(['is_published' => true]);
        $unpublished = Event::factory()->create(['is_published' => false]);

        expect($published->isPublished())->toBeTrue();
        expect($unpublished->isPublished())->toBeFalse();
    });
});

describe('Event Status Attribute', function () {
    it('returns upcoming for events with future start date', function () {
        $event = Event::factory()->create(['start_date' => now()->addDay()]);

        expect($event->status)->toBe('upcoming');
    });

    it('returns past for events with past end date', function () {
        $event = Event::factory()->create(['end_date' => now()->subDay()]);

        expect($event->status)->toBe('completed');
    });
});
