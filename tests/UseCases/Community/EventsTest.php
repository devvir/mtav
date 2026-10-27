<?php

use App\Enums\EventType;
use App\Models\Event;

/**
 * Admins publish events (meetings, assemblies, social activities), on site or online.
 * Drafts are visible to admins only. Events may ask members to confirm attendance.
 * The lottery is an event of its own, created with the project.
 */

beforeEach(function () {
    $this->project = aProject();
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf(aFamily($this->project));
});

function eventData(array $overrides = []): array
{
    return [
        'type'         => EventType::ONSITE->value,
        'title'        => 'General assembly',
        'description'  => 'Yearly general assembly',
        'location'     => 'Community hall',
        'start_date'   => now()->addWeek()->format('Y-m-d H:i'),
        'end_date'     => now()->addWeek()->addHours(2)->format('Y-m-d H:i'),
        'is_published' => true,
        ...$overrides,
    ];
}

function listedEventIds($response): array
{
    return collect($response->inertiaProps('events.data'))->pluck('id')->all();
}

describe('An admin', function () {
    it('publishes an event that members then see', function () {
        $this->as($this->admin, $this->project)
            ->post(route('manage.events.store'), eventData())
            ->assertSessionHasNoErrors();

        $event = $this->project->events()->firstWhere('title', 'General assembly');

        expect($event)->not->toBeNull()
            ->and(listedEventIds($this->as($this->member, $this->project)->get(route('events.index'))))
            ->toContain($event->id);

        $this->as($this->member, $this->project)->get(route('events.show', $event))->assertOk();
    });

    it('keeps a draft event hidden from members', function () {
        $draft = anEvent($this->project, ['is_published' => false]);

        expect(listedEventIds($this->as($this->member, $this->project)->get(route('events.index'))))
            ->not->toContain($draft->id);

        expect($this->as($this->member, $this->project)->get(route('events.show', $draft)))->toBeDenied();

        expect(listedEventIds($this->as($this->admin, $this->project)->get(route('manage.events.index'))))->toContain($draft->id);
    });

    it('changes and cancels an event', function () {
        $event = anEvent($this->project);

        $this->as($this->admin, $this->project)
            ->put(route('manage.events.update', $event), eventData(['title' => 'Moved assembly']))
            ->assertSessionHasNoErrors();

        expect($event->fresh()->title)->toBe('Moved assembly');

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $event));

        expect(Event::find($event->id))->toBeNull();
    });

    it('cannot end an event before it starts', function () {
        $this->as($this->admin, $this->project)->post(route('manage.events.store'), eventData([
            'start_date' => now()->addWeek()->format('Y-m-d H:i'),
            'end_date'   => now()->addDay()->format('Y-m-d H:i'),
        ]))->assertSessionHasErrors('end_date');
    });

    it('gives an online event a link as its location', function () {
        $this->as($this->admin, $this->project)->post(route('manage.events.store'), eventData([
            'type'     => EventType::ONLINE->value,
            'location' => 'not a link',
        ]))->assertSessionHasErrors('location');
    });

    it('asks members to confirm attendance', function () {
        $this->as($this->admin, $this->project)
            ->post(route('manage.events.store'), eventData(['allows_rsvp' => true]))
            ->assertSessionHasNoErrors();

        expect($this->project->events()->firstWhere('title', 'General assembly')->allows_rsvp)->toBeTrue();
    });
});

describe('A member', function () {
    it('cannot create, change or cancel events', function () {
        $event = anEvent($this->project, ['title' => 'Original']);

        expect($this->as($this->member, $this->project)->post(route('manage.events.store'), eventData()))
            ->toBeDenied();

        expect($this->as($this->member, $this->project)->put(route('manage.events.update', $event), eventData()))
            ->toBeDenied();

        expect($this->as($this->member, $this->project)->delete(route('manage.events.destroy', $event)))
            ->toBeDenied();

        expect($this->project->events()->where('title', 'General assembly')->exists())->toBeFalse()
            ->and($event->fresh())->title->toBe('Original');
    });

    it('confirms or declines attendance to an event that asks for it', function () {
        $event = anEvent($this->project, ['allows_rsvp' => true]);

        $this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => true]);
        expect($event->rsvps()->whereKey($this->member->id)->first()?->pivot->attending)->toBe(1);

        $this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => false]);
        expect($event->rsvps()->whereKey($this->member->id)->first()?->pivot->attending)->toBe(0);
    });

    it('cannot answer an event that does not ask for attendance', function () {
        $event = anEvent($this->project, ['allows_rsvp' => false]);

        expect($this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => true]))
            ->toBeDenied();
    });
});

describe('The lottery event', function () {
    it('exists from the moment the project is created', function () {
        expect($this->project->lottery)->not->toBeNull()
            ->isLottery()->toBeTrue();
    });

    it('cannot be cancelled', function () {
        $lottery = $this->project->lottery;

        $this->as($this->admin, $this->project)->delete(route('manage.events.destroy', $lottery));

        expect($lottery->fresh())->not->toBeNull();
    });

    it('can be scheduled, but keeps its title', function () {
        $lottery = $this->project->lottery;
        $date = now()->addMonth()->startOfMinute();

        $this->as($this->admin, $this->project)->put(route('manage.lottery.schedule'), [
            'title'       => 'Renamed lottery',
            'description' => 'Draw for all units',
            'start_date'  => $date->format('Y-m-d H:i'),
        ]);

        expect($lottery->fresh())
            ->title->toBe($this->project->lottery->title)
            ->description->toBe('Draw for all units')
            ->start_date->toEqual($date);
    });
});
