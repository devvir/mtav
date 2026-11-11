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

        $this->as($this->member, $this->project)->get(route('events.show', $event))
            ->assertRedirect(route('events.index', ['event' => $event->id]));

        expect($this->get(route('events.index', ['event' => $event->id]))->inertiaProps('event.id'))
            ->toBe($event->id);
    });

    it('keeps a draft event hidden from members', function () {
        $draft = anEvent($this->project, ['is_published' => false]);

        expect(listedEventIds($this->as($this->member, $this->project)->get(route('events.index'))))
            ->not->toContain($draft->id);

        expect($this->as($this->member, $this->project)->get(route('events.show', $draft)))->toBeDenied()
            ->and($this->get(route('events.index', ['event' => $draft->id]))->inertiaProps('event'))->toBeNull();

        // Admins see drafts on the same list, to publish them when ready
        expect(listedEventIds($this->as($this->admin, $this->project)->get(route('events.index'))))->toContain($draft->id);
    });

    it('cannot edit the lottery event as an event (it is scheduled with the lottery)', function () {
        expect($this->as($this->admin, $this->project)
            ->put(route('manage.events.update', $this->project->lottery), eventData()))
            ->toBeDenied();
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
        // A web link only: never a file or script address
        $this->as($this->admin, $this->project)->post(route('manage.events.store'), eventData([
            'type'     => EventType::ONLINE->value,
            'location' => 'file:///etc/passwd',
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
    it('sees the coming events soonest first, and past ones latest first', function () {
        $later = anEvent($this->project, ['start_date' => now()->addMonth()]);
        $sooner = anEvent($this->project, ['start_date' => now()->addDay()]);
        $longAgo = anEvent($this->project, ['start_date' => now()->subMonth()]);
        $recently = anEvent($this->project, ['start_date' => now()->subDay()]);

        $member = $this->as($this->member, $this->project);

        // The lottery leads the coming events, dated or not
        expect(listedEventIds($member->get(route('events.index'))))->toBe([$this->project->lottery->id, $sooner->id, $later->id])
            ->and(listedEventIds($member->get(route('events.index', ['when' => 'past']))))->toBe([$recently->id, $longAgo->id]);
    });

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

    it('tells the page exactly what someone answered: the buttons light up on true or false', function () {
        $event = anEvent($this->project, ['allows_rsvp' => true]);
        $answer = fn () => collect($this->as($this->member, $this->project)->get(route('events.index'))->inertiaProps('events.data'))
            ->firstWhere('id', $event->id)['attending'];

        expect($answer())->toBeNull();

        $this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => true]);
        expect($answer())->toBeTrue();

        $this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => false]);
        expect($answer())->toBeFalse();
    });

    it('cannot answer an event that does not ask for attendance', function () {
        $event = anEvent($this->project, ['allows_rsvp' => false]);

        expect($this->as($this->member, $this->project)->put(route('events.rsvp', $event), ['attending' => true]))
            ->toBeDenied();
    });
});

describe('The events list', function () {
    it('keeps an event under way among the upcoming ones until it ends', function () {
        $event = fn (array $dates) => $this->project->events()->create([...eventData(), ...$dates]);
        $under = $event(['start_date' => now()->subMinutes(10), 'end_date' => now()->addHour()]);
        $noEnd = $event(['start_date' => now()->subMinutes(10), 'end_date' => null]);
        $over = $event(['start_date' => now()->subHours(3), 'end_date' => now()->subHour()]);

        $this->as($this->member, $this->project);
        $upcoming = listedEventIds($this->get(route('events.index')));
        $past = listedEventIds($this->get(route('events.index', ['when' => 'past'])));

        expect($upcoming)->toContain($under->id, $noEnd->id)->not->toContain($over->id)
            ->and($past)->toContain($over->id)->not->toContain($under->id, $noEnd->id);
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

describe('The lottery\'s own words', function () {
    function lotteryAsReadBy($test, $user, $project): array
    {
        return $test->as($user, $project)->get(route('lottery.show'))->inertiaProps('lottery');
    }

    beforeEach(function () {
        $this->creator = aUser(['locale' => 'es_UY']);
        app()->setLocale('es_UY');
        $this->started = app(App\Services\ProjectService::class)->create($this->creator, ['name' => 'Los Ceibos', 'description' => 'A place']);
        app()->setLocale('en');
        $this->reader = aMemberOf(aFamily($this->started, aUnitType($this->started)), ['locale' => 'pt_BR']);
    });

    it('reach each reader in their language, whoever started the project', function () {
        expect(lotteryAsReadBy($this, $this->reader, $this->started))
            ->title->toBe(trans('lottery.title', [], 'pt_BR'))
            ->description->toBe(trans('lottery.default_description', [], 'pt_BR'));
        expect(lotteryAsReadBy($this, $this->creator, $this->started))
            ->title->toBe(trans('lottery.title', [], 'es_UY'))
            ->description->toBe(trans('lottery.default_description', [], 'es_UY'));
    });

    it('stay so when admins set just the date, leaving the words they were shown', function () {
        $this->as($this->creator, $this->started)->put(route('manage.lottery.schedule'), [
            'description' => trans('lottery.default_description', [], 'es_UY'),
            'start_date'  => now()->addWeek()->toDateTimeString(),
        ])->assertSessionHasNoErrors();

        expect(lotteryAsReadBy($this, $this->reader, $this->started)['description'])->toBe(trans('lottery.default_description', [], 'pt_BR'));
    });

    it('give way to what admins write, for everyone, under the same title', function () {
        $this->as($this->creator, $this->started)->put(route('manage.lottery.schedule'), [
            'description' => 'Nos juntamos en el salón',
        ])->assertSessionHasNoErrors();

        expect(lotteryAsReadBy($this, $this->reader, $this->started))
            ->title->toBe(trans('lottery.title', [], 'pt_BR'))
            ->description->toBe('Nos juntamos en el salón');
    });

    it('leave other events as they were written', function () {
        $event = anEvent($this->started, ['title' => 'Asamblea', 'description' => 'En el salón']);

        $seen = App\Http\Resources\EventResource::make($event)->resolve(request());

        expect($seen)->title->toBe('Asamblea')->description->toBe('En el salón');
    });
});

describe('An event\'s dates', function () {
    it('need a start for an end: an event with only an end is refused, not to come for ever', function () {
        $this->as($this->admin, $this->project)->post(route('manage.events.store'), eventData([
            'start_date' => null,
            'end_date'   => now()->addWeek()->format('Y-m-d H:i'),
        ]))->assertSessionHasErrors('start_date');

        $event = anEvent($this->project, ['title' => 'Kept']);
        $this->put(route('manage.events.update', $event), eventData(['start_date' => null, 'end_date' => now()->addDay()->format('Y-m-d H:i')]))
            ->assertSessionHasErrors('start_date');

        expect($this->project->events()->where('title', 'General assembly')->exists())->toBeFalse()
            ->and($event->fresh()->end_date)->toBeNull();
    });

    it('may be none at all, or only a start', function () {
        $this->as($this->admin, $this->project);

        $this->post(route('manage.events.store'), eventData(['title' => 'Undated', 'start_date' => null, 'end_date' => null]))->assertSessionHasNoErrors();
        $this->post(route('manage.events.store'), eventData(['title' => 'Started', 'end_date' => null]))->assertSessionHasNoErrors();

        expect($this->project->events()->whereIn('title', ['Undated', 'Started'])->count())->toBe(2);
    });
});

describe('Answering whether one attends', function () {
    $answer = fn ($test, $event, $user = null) => $test->as($user ?? $test->member, $test->project)->put(route('events.rsvp', $event), ['attending' => true]);

    it('is not for a draft', function () use ($answer) {
        $draft = anEvent($this->project, ['allows_rsvp' => true, 'is_published' => false]);

        expect($answer($this, $draft, $this->admin))->toBeDenied();
        expect($draft->rsvps()->count())->toBe(0);
    });

    it('is not for the lottery, which asks nobody', function () use ($answer) {
        $lottery = $this->project->lottery;
        $lottery->update(['allows_rsvp' => true, 'is_published' => true]);

        expect($answer($this, $lottery))->toBeDenied();
        expect($lottery->rsvps()->count())->toBe(0);
    });

    it('ends with the event, as its page shows', function () use ($answer) {
        $over = anEvent($this->project, ['allows_rsvp' => true, 'start_date' => now()->subDay(), 'end_date' => now()->subDay()->addHours(2)]);
        $underWay = anEvent($this->project, ['allows_rsvp' => true, 'start_date' => now()->subMinutes(10), 'end_date' => now()->addHour()]);

        expect($answer($this, $over))->toBeDenied();
        $answer($this, $underWay)->assertSessionHasNoErrors();

        expect($over->rsvps()->count())->toBe(0)->and($underWay->rsvps()->count())->toBe(1);
    });

    it('stops when the event stops asking, keeping the answers given, back as they were if it asks again', function () use ($answer) {
        $event = anEvent($this->project, ['allows_rsvp' => true]);
        $answer($this, $event)->assertSessionHasNoErrors();
        $attendees = fn () => collect($this->as($this->member, $this->project)->get(route('events.index'))->inertiaProps('events.data'))
            ->firstWhere('id', $event->id);

        $this->as($this->admin, $this->project)->put(route('manage.events.update', $event), eventData(['allows_rsvp' => false]))->assertSessionHasNoErrors();

        expect($answer($this, $event))->toBeDenied();
        expect($attendees())->allows_rsvp->toBeFalse()->and($attendees()['can']['rsvp'])->toBeFalse()
            ->and($event->rsvps()->count())->toBe(1);

        $this->as($this->admin, $this->project)->put(route('manage.events.update', $event), eventData(['allows_rsvp' => true]));

        expect($attendees())->attending->toBeTrue()->and(collect($attendees()['attendees'])->pluck('id')->all())->toBe([$this->member->id]);
    });
});

it('takes an event\'s words of their longest, in characters of four bytes, and refuses one more', function () {
    $long = fn (int $characters) => str_repeat('😀', $characters);
    $this->as($this->admin, $this->project);

    $this->post(route('manage.events.store'), eventData(['title' => $long(161)]))->assertSessionHasErrors('title');
    $this->post(route('manage.events.store'), eventData(['description' => $long(5001)]))->assertSessionHasErrors('description');
    $this->post(route('manage.events.store'), eventData(['title' => $long(160), 'description' => $long(5000)]))->assertSessionHasNoErrors();

    expect($this->project->events()->firstWhere('title', $long(160))?->description)->toBe($long(5000));
});
