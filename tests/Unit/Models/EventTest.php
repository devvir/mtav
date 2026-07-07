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

        $audit = \App\Models\LotteryAudit::create([
            'execution_uuid' => \Illuminate\Support\Str::uuid()->toString(),
            'project_id'     => $lotteryEvent->project_id,
            'lottery_id'     => $lotteryEvent->id,
            'type'           => 'init',
            'audit'          => [],
        ]);

        expect($lotteryEvent->audits->pluck('id'))->toCollect($audit->id);
    });
});

/**
 * Scope tests assert exact Event IDs from the universe fixture (14 events,
 * visible in full because these tests run unauthenticated / unscoped).
 */
describe('Event Model Scopes - Type Filters', function () {
    it('filters lottery events', function () {
        expect(Event::lottery()->pluck('id'))->toCollect(1, 6, 10, 13, 14);
    });

    it('filters online events', function () {
        expect(Event::online()->pluck('id'))->toCollect(2, 3, 7, 11);
    });

    it('filters onsite events', function () {
        expect(Event::onsite()->pluck('id'))->toCollect(4, 5, 8, 9, 12);
    });
});

describe('Event Model Scopes - Publication Status', function () {
    it('filters published events', function () {
        // All events except unpublished #5 and #12
        expect(Event::published()->pluck('id'))->toCollect(1, 2, 3, 4, 6, 7, 8, 9, 10, 11, 13, 14);
    });
});

describe('Event Model Scopes - Temporal Filters', function () {
    it('filters upcoming events', function () {
        // Future start dates: #1(+30d), #2(+7d), #4(+14d), #5(+21d), #6(+45d),
        // #7(+10d), #8(+3d), #11(+5d), #12(+12d)
        expect(Event::upcoming()->pluck('id'))->toCollect(1, 2, 4, 5, 6, 7, 8, 11, 12);
    });

    it('includes events without a start date (TBD) in upcoming', function () {
        $tbd = Event::factory()->create(['start_date' => null, 'end_date' => null]);

        expect(Event::upcoming()->pluck('id'))->toContain($tbd->id);
    });

    it('filters past events', function () {
        // Explicitly ended: #3(-5d), #9(-10d).
        // Implicitly ended (no end date, started beyond IMPLICIT_DURATION): #10, #13, #14
        expect(Event::past()->pluck('id'))->toCollect(3, 9, 10, 13, 14);
    });

    it('filters ongoing events', function () {
        // No fixture event is ongoing; create one that started recently (within
        // the implicit duration) and has no end date.
        $ongoing = Event::factory()->create([
            'start_date' => now()->subMinutes(Event::IMPLICIT_DURATION - 10),
            'end_date'   => null,
        ]);

        expect(Event::ongoing()->pluck('id'))->toCollect($ongoing->id);
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
        // Event #2: Online meeting +7 days from now
        $event = Event::find(2);

        expect($event->status)->toBe('upcoming');
    });

    it('returns completed for events with past end date', function () {
        // Event #3: Past online workshop -5 days
        $event = Event::find(3);

        expect($event->status)->toBe('completed');
    });

    it('returns ongoing for events that started but not ended', function () {
        // Use event #4 but adjust dates to make it ongoing
        $event = Event::find(4);
        $event->update([
            'start_date' => now()->subHour(),
            'end_date'   => now()->addHour(),
        ]);

        expect($event->fresh()->status)->toBe('ongoing');
    });

    it('returns completed for events with implicit duration that passed', function () {
        // Event #1: Lottery with no end_date, adjust to be past
        $event = Event::find(1);
        $event->update([
            'start_date' => now()->subHours(2),
            'end_date'   => null, // Uses IMPLICIT_DURATION (60 minutes)
        ]);

        expect($event->fresh()->status)->toBe('completed');
    });
});
