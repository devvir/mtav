<?php

use App\Jobs\PruneLogs;
use App\Models\Activity;
use App\Models\ErrorEntry;
use App\Services\Queue\Workers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * What only piles up goes, a day apart at most, with no scheduler: failed sign-ins, errors
 * that stopped, failed jobs, expired cache rows. The audit stays.
 */

function anEntry(string $action, int $daysAgo): Activity
{
    return Activity::create(['action' => $action, 'created_at' => now()->subDays($daysAgo)]);
}

function anError(int $daysAgo): ErrorEntry
{
    return ErrorEntry::create([
        'fingerprint'   => Str::random(40), 'class' => 'E', 'message' => 'm',
        'first_seen_at' => now()->subDays($daysAgo), 'last_seen_at' => now()->subDays($daysAgo),
    ]);
}

it('lets go failed sign-ins past their days, and only those', function () {
    $old = anEntry('sign_in_failed', 91);
    $recent = anEntry('sign_in_failed', 89);
    $audit = [anEntry('signed_in', 4000), anEntry('updated', 4000), anEntry('password_reset', 4000)];

    (new PruneLogs())->handle();

    expect(Activity::find($old->id))->toBeNull()
        ->and(Activity::find($recent->id))->not->toBeNull()
        ->and(Activity::whereIn('id', collect($audit)->pluck('id'))->count())->toBe(3);
});

it('gets through more than one batch of them', function () {
    Activity::insert(array_fill(0, 1001, ['action' => 'sign_in_failed', 'created_at' => now()->subDays(100)]));

    (new PruneLogs())->handle();

    expect(Activity::count())->toBe(0);
});

it('lets go errors that stopped happening, not those that go on', function () {
    $stopped = anError(181);
    $goesOn = anError(179);

    (new PruneLogs())->handle();

    expect(ErrorEntry::find($stopped->id))->toBeNull()
        ->and(ErrorEntry::find($goesOn->id))->not->toBeNull();
});

it('lets go jobs that failed long ago', function () {
    foreach (['old' => 31, 'recent' => 29] as $uuid => $days) {
        DB::table('failed_jobs')->insert([
            'uuid'      => $uuid, 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'x',
            'failed_at' => now()->subDays($days),
        ]);
    }

    (new PruneLogs())->handle();

    expect(DB::table('failed_jobs')->pluck('uuid')->all())->toBe(['recent']);
});

it('lets go expired cache rows where the cache is the database, not live ones', function () {
    config(['cache.default' => 'database']);
    DB::table('cache')->insert([
        ['key' => 'gone', 'value' => 'x', 'expiration' => now()->subMinute()->timestamp],
        ['key' => 'live', 'value' => 'x', 'expiration' => now()->addHour()->timestamp],
    ]);

    (new PruneLogs())->handle();

    expect(DB::table('cache')->pluck('key')->all())->toBe(['live']);
});

it('leaves the cache table alone where the cache is elsewhere', function () {
    DB::table('cache')->insert(['key' => 'gone', 'value' => 'x', 'expiration' => now()->subMinute()->timestamp]);

    (new PruneLogs())->handle();

    expect(DB::table('cache')->count())->toBe(1);
});

it('is asked for by a working worker, once a day at most', function () {
    Queue::fake();

    Workers::mark();
    $this->travel(1)->minutes();
    Workers::mark();
    Queue::assertPushedTimes(PruneLogs::class, 1);

    $this->travel(1)->days();
    Workers::mark();
    Queue::assertPushedTimes(PruneLogs::class, 2);
});

it('is not asked for on a loop that writes no mark', function () {
    Queue::fake();
    Cache::put('queue.worker-seen', now()->timestamp, 600);

    Workers::mark();

    Queue::assertNotPushed(PruneLogs::class);
});
