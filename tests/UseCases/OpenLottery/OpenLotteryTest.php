<?php

use App\Enums\LotteryRunStatus;
use App\Models\OpenLottery;
use App\Models\PlatformSetting;
use App\Services\OpenLotteryService;
use App\Services\Queue\Workers;

/**
 * No accounts: someone creates a lottery, people join by its link and rank what it gives out, the
 * organizer runs it, once, and anyone with the link checks the result.
 */

function aLottery(array $items = ['Blue room', 'Green room', 'Attic']): array
{
    return app(OpenLotteryService::class)->create(['name' => 'August', 'items_name' => 'Rooms', 'items' => $items]);
}

function openPage($test, OpenLottery $lottery, ?string $me = null, ?string $organizer = null)
{
    $me && $test->withCookie("open_me_{$lottery->slug}", $me);
    $organizer && $test->withCookie("open_organizer_{$lottery->slug}", $organizer);

    return $test->get(route('open.show', $lottery));
}

function asPerson($test, OpenLottery $lottery, string $key)
{
    return $test->withCookie("open_me_{$lottery->slug}", $key);
}

function asOrganizer($test, OpenLottery $lottery, string $key)
{
    return $test->withCookie("open_organizer_{$lottery->slug}", $key);
}

function joined(OpenLottery $lottery, string $name): array
{
    return app(OpenLotteryService::class)->join($lottery, $name);
}

beforeEach(function () {
    config(['variant.name' => 'open_lottery']);
    Workers::mark();
    PlatformSetting::put(PlatformSetting::LOTTERY_SECONDS, 0);
});

describe('Creating one', function () {
    it('starts on the landing page, with nothing to sign in to', function () {
        $this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page->component('Open/Landing'));
    });

    it('gives a short link, and makes its creator the organizer', function () {
        $this->post(route('open.store'), ['name' => 'August', 'items_name' => 'Rooms', 'items' => ['Attic']])
            ->assertRedirect()->assertCookie('open_organizer_' . OpenLottery::sole()->slug);

        expect(OpenLottery::sole()->slug)->toHaveLength(10)
            ->and(OpenLottery::sole()->items()->pluck('name')->all())->toBe(['Attic']);
    });

    it('never stores the organizer\'s secret, only its hash', function () {
        [$lottery, $key] = aLottery();

        expect($lottery->organizer_key)->not->toBe($key)->and($lottery->isOrganizer($key))->toBeTrue();
    });

    it('says so when items differ only in their accents', function () {
        $this->post(route('open.store'), ['name' => 'August', 'items_name' => 'Rooms', 'items' => ['Año', 'Ano']])
            ->assertSessionHasErrors('items');

        expect(OpenLottery::count())->toBe(0);
    });

    it('limits how many one address creates', function () {
        foreach (range(1, 10) as $i) {
            $this->post(route('open.store'), ['name' => "Lottery {$i}", 'items_name' => 'Rooms']);
        }

        $this->post(route('open.store'), ['name' => 'One more', 'items_name' => 'Rooms'])
            ->assertSessionHas('error', __('flash.too_many_attempts'));

        expect(OpenLottery::count())->toBe(10);
    });
});

describe('Joining and ranking', function () {
    it('shows everyone who is in and whether they ranked, never what', function () {
        [$lottery] = aLottery();
        [$ana, $key] = joined($lottery, 'Ana');
        joined($lottery, 'Beto');
        $item = $lottery->items()->first();

        asPerson($this, $lottery, $key)->put(route('open.rank', $lottery), ['ranking' => [$item->id]])->assertRedirect();

        $this->defaultCookies = [];
        $page = $this->get(route('open.show', $lottery))->assertOk();
        $people = collect($page->inertiaProps('people'));

        expect($people->pluck('ranked', 'name')->all())->toBe(['Ana' => true, 'Beto' => false])
            ->and($page->inertiaProps('me'))->toBeNull()
            ->and(json_encode($people))->not->toContain('ranking');
    });

    it('refuses a name already taken, whatever its case', function () {
        [$lottery] = aLottery();
        joined($lottery, 'Ana');

        $this->post(route('open.join', $lottery), ['name' => 'ANA'])->assertSessionHasErrors('name');
    });

    it('stops at its size', function () {
        config(['limits.open_lottery.participants' => 1]);
        [$lottery] = aLottery();
        joined($lottery, 'Ana');

        $this->post(route('open.join', $lottery), ['name' => 'Beto'])->assertSessionHasErrors('name');
    });

    it('takes no item of another lottery in a ranking', function () {
        [$lottery] = aLottery();
        [$other] = aLottery(['Elsewhere']);
        [, $key] = joined($lottery, 'Ana');

        asPerson($this, $lottery, $key)
            ->put(route('open.rank', $lottery), ['ranking' => [$other->items()->first()->id]])
            ->assertStatus(422);
    });

    it('lets someone in on another device with their own link', function () {
        [$lottery] = aLottery();
        [$ana, $key] = joined($lottery, 'Ana');

        $this->get(route('open.me', [$lottery, $key]))->assertRedirect(route('open.show', $lottery))
            ->assertCookie("open_me_{$lottery->slug}");
        $this->get(route('open.me', [$lottery, 'wrong']))->assertNotFound();
    });

    it('keeps a person\'s ranking when an item goes, without what went', function () {
        [$lottery] = aLottery();
        [$ana, $key] = joined($lottery, 'Ana');
        [$first, $second] = $lottery->items()->get()->all();
        app(OpenLotteryService::class)->rank($lottery, $ana, [$first->id, $second->id]);

        app(OpenLotteryService::class)->removeItem($lottery, $first);

        expect($ana->ranking()->pluck('open_lottery_items.id')->all())->toBe([$second->id]);
    });
});

describe('What only the organizer does', function () {
    it('refuses it from anyone else', function () {
        [$lottery] = aLottery();
        [$ana, $key] = joined($lottery, 'Ana');

        expect(asPerson($this, $lottery, $key)->post(route('open.run', $lottery)))->toBeDenied()
            ->and($this->post(route('open.items.store', $lottery), ['name' => 'Tent']))->toBeDenied()
            ->and($this->delete(route('open.items.remove', [$lottery, $lottery->items()->first()])))->toBeDenied()
            ->and($this->delete(route('open.people.remove', [$lottery, $ana])))->toBeDenied();
    });

    it('takes someone out, who is told so and may join again', function () {
        [$lottery, $organizer] = aLottery();
        [$ana, $key] = joined($lottery, 'Ana');

        asOrganizer($this, $lottery, $organizer)->delete(route('open.people.remove', [$lottery, $ana]))->assertRedirect();
        $this->defaultCookies = [];

        $page = openPage($this, $lottery, me: $key)->assertOk();

        expect($page->inertiaProps('removed'))->toBeTrue()
            ->and($page->inertiaProps('people'))->toBe([]);

        $this->post(route('open.join', $lottery), ['name' => 'Ana'])->assertSessionHasNoErrors();
    });

    it('does not reach an item or a person of another lottery', function () {
        [$lottery, $organizer] = aLottery();
        [$other] = aLottery(['Elsewhere']);
        [$stranger] = joined($other, 'Stranger');

        asOrganizer($this, $lottery, $organizer)->delete(route('open.items.remove', [$lottery, $other->items()->first()]))->assertNotFound();
        asOrganizer($this, $lottery, $organizer)->delete(route('open.people.remove', [$lottery, $stranger]))->assertNotFound();
    });
});

describe('Running it', function () {
    function ready(array $items = ['Blue room', 'Green room']): array
    {
        [$lottery, $organizer] = aLottery($items);
        [$ana] = joined($lottery, 'Ana');
        [$beto] = joined($lottery, 'Beto');

        return [$lottery, $organizer, $ana, $beto];
    }

    it('takes at least 2 people', function () {
        [$lottery, $organizer] = aLottery();
        joined($lottery, 'Ana');

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true])->assertSessionHasErrors('run');
    });

    it('asks to confirm who has not ranked, and counts that differ', function () {
        [$lottery, $organizer] = ready(['Blue room']);

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery))->assertSessionHasErrors('run');

        expect($lottery->runs()->count())->toBe(0);
    });

    it('gives each their first choice when they do not collide, with everyone seeing it', function () {
        [$lottery, $organizer, $ana, $beto] = ready();
        [$blue, $green] = $lottery->items()->get()->all();
        $service = app(OpenLotteryService::class);
        $service->rank($lottery, $ana, [$green->id, $blue->id]);
        $service->rank($lottery, $beto, [$blue->id, $green->id]);

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery))->assertSessionHasNoErrors();
        $this->defaultCookies = [];

        $page = $this->get(route('open.show', $lottery));

        expect($page->inertiaProps('state'))->toBe('decided')
            ->and(collect($page->inertiaProps('people'))->pluck('item', 'name')->all())
            ->toBe(['Ana' => 'Green room', 'Beto' => 'Blue room']);
    });

    it('still decides when the counts differ, saying who gets nothing', function () {
        [$lottery, $organizer] = ready(['Blue room']);

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true])->assertSessionHasNoErrors();
        $this->defaultCookies = [];

        $people = collect($this->get(route('open.show', $lottery))->inertiaProps('people'));

        expect($people->pluck('item')->filter()->count())->toBe(1);
    });

    it('runs once, and nothing in it changes afterwards', function () {
        [$lottery, $organizer, $ana] = ready();

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true]);
        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true])->assertSessionHasErrors('run');
        asOrganizer($this, $lottery, $organizer)->post(route('open.items.store', $lottery), ['name' => 'Tent'])->assertSessionHasErrors('lottery');
        $this->post(route('open.join', $lottery), ['name' => 'Late'])->assertSessionHasErrors('lottery');

        expect($lottery->runs()->count())->toBe(1);
    });

    it('may try again when a run decided nothing', function () {
        [$lottery, $organizer] = ready();
        $run = $lottery->runs()->create(['uuid' => 'x', 'status' => LotteryRunStatus::RUNNING, 'solver' => 'glpk', 'started_at' => now()]);
        app(OpenLotteryService::class)->fail($run, 'It broke');

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true])->assertSessionHasNoErrors();

        expect($lottery->fresh()->runs()->where('status', LotteryRunStatus::COMPLETED)->count())->toBe(1);
    });

    it('needs someone to do the work', function () {
        [$lottery, $organizer] = ready();
        config(['queue.default' => 'database']);
        cache()->flush();

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true])->assertSessionHasErrors('run');
    });
});

describe('The record', function () {
    it('is there for anyone with the link only once it decided', function () {
        [$lottery, $organizer] = aLottery();
        [$ana] = joined($lottery, 'Ana');
        joined($lottery, 'Beto');
        app(OpenLotteryService::class)->rank($lottery, $ana, [$lottery->items()->first()->id]);

        $this->get(route('open.record', $lottery))->assertNotFound();

        asOrganizer($this, $lottery, $organizer)->post(route('open.run', $lottery), ['confirmed' => true]);
        $this->defaultCookies = [];

        $this->get(route('open.record', $lottery))->assertOk()
            ->assertJsonPath('people.0.name', 'Ana')
            ->assertJsonPath('people.0.ranking.0', 'Blue room')
            ->assertJsonStructure(['fingerprint', 'audit' => [['type', 'audit']]]);
    });
});

describe('Lotteries nobody touches', function () {
    it('go after the days they are kept', function () {
        [$old] = aLottery();
        OpenLottery::whereKey($old->id)->toBase()->update(['updated_at' => now()->subDays(91)]);
        [$recent] = aLottery();

        (new App\Jobs\PurgeStaleOpenLotteries())->handle();

        expect(OpenLottery::pluck('id')->all())->toBe([$recent->id]);
    });
});

describe('The rest of the app', function () {
    it('does not exist here', function () {
        foreach (['login', 'register', 'projects', 'admin', 'manual', 'settings/profile', 'directory', 'notifications', 'documentation/guide', 'invitations/x'] as $path) {
            $this->get("/{$path}")->assertNotFound();
        }

        $this->post('/login', ['email' => 'a@example.com', 'password' => 'x'])->assertNotFound();
        $this->post('/register')->assertNotFound();
    });

    it('sends the browser no route of what does not exist', function () {
        $routes = $this->get(route('home'))->inertiaProps('routes.routes');

        expect($routes)->toHaveKey('open.show')->not->toHaveKey('login')->not->toHaveKey('admin.projects.index');
    });

    it('waits for no owner', function () {
        $this->get(route('home'))->assertOk();
        expect(App\Models\User::count())->toBe(0);
    });

    it('does not exist in MTAV either way round', function () {
        config(['variant.name' => 'mtav']);

        $this->post('/l', ['name' => 'August', 'items_name' => 'Rooms'])->assertNotFound();
    });
});

it('never lets a feature a variant does not offer exist in a project', function () {
    config(['variant.name' => 'mtav']);
    $project = aProject();
    expect($project->hasFeature('forum'))->toBeTrue();

    config(['variants.mtav.features' => ['chat' => ['default' => true, 'configurable' => false]]]);

    expect($project->hasFeature('forum'))->toBeFalse()
        ->and($project->features()['chat'])->toBeTrue();
    $this->as(anAdminOf($project), $project)->get(route('forum.index'))->assertNotFound();
});
