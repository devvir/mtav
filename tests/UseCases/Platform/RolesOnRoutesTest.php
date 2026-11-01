<?php

use App\Enums\LotteryRunStatus;
use App\Models\ErrorEntry;
use App\Services\ErrorLog;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

/**
 * Every admin tool refuses whoever does not manage the project, and the platform console whoever
 * does not run the platform. The routes are read from the app, so a new one is covered as
 * soon as it exists, and each address is filled with real records: one this file cannot fill
 * fails here, since a made-up record would be refused for not existing, and prove nothing.
 */

beforeEach(function () {
    Notification::fake();
    config(['errors.log' => true]);

    $this->project = aProject();
    $this->type = aUnitType($this->project);
    $this->family = aFamily($this->project, $this->type);
    $this->admin = anAdminOf($this->project);
    $this->member = aMemberOf($this->family);
    $this->run = aLotteryInProgress($this->project);
    $this->run->update(['status' => LotteryRunStatus::COMPLETED]);

    ErrorLog::capture(new RuntimeException('Something went wrong'));

    $event = anEvent($this->project);

    $this->records = [
        'project'     => $this->project,
        'family'      => $this->family,
        'member'      => aMemberOf($this->family),
        'unitType'    => $this->type,
        'unit'        => aUnit($this->type),
        'event'       => $event,
        'lotteryRun'  => $this->run,
        'invitation'  => $this->project->invitations()->create(['email' => 'invited@example.com', 'token_hash' => 'x', 'expires_at' => now()->addDay()]),
        'joinRequest' => $this->project->joinRequests()->create(['user_id' => aUser()->id]),
        'kind'        => 'events',
        'id'          => $event->id,
        'user'        => aUser(),
        'error'       => ErrorEntry::sole(),
    ];
});

/** @return array<string, RoutingRoute> By name: the routes of an area, and any others named */
function routesOf(string $area, string ...$others): array
{
    return collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route) => str_starts_with((string) $route->getName(), $area) || in_array($route->getName(), $others, true))
        ->mapWithKeys(fn (RoutingRoute $route) => [$route->getName() => $route])
        ->all();
}

/** The route's address, with the real records it names */
function addressOf(RoutingRoute $route, array $records): string
{
    $missing = array_diff($route->parameterNames(), array_keys($records));
    expect($missing)->toBe([], "{$route->getName()} names something this test has no record for");

    return route($route->getName(), array_intersect_key($records, array_flip($route->parameterNames())));
}

it('refuses the project\'s admin tools to one of its members, for their role and not for a missing record', function () {
    $routes = routesOf('manage.', 'units.edit');
    expect($routes)->not->toBeEmpty();

    foreach ($routes as $name => $route) {
        $response = $this->as($this->member)->call($route->methods()[0], addressOf($route, $this->records));

        expect($response)->toBeDenied($name);
        expect($response->status())->not->toBe(404, "{$name} is missing its records, so this says nothing");
    }
});

it('refuses the project\'s admin tools to the admin of another project', function () {
    $outsider = anAdminOf(aProject());

    foreach (routesOf('manage.', 'units.edit') as $name => $route) {
        expect($this->as($outsider)->call($route->methods()[0], addressOf($route, $this->records)))->toBeDenied($name);
    }
});

it('refuses the platform console to anyone but superadmins', function () {
    $routes = routesOf('admin.');
    expect($routes)->not->toBeEmpty();

    foreach ([$this->member, $this->admin] as $user) {
        foreach ($routes as $name => $route) {
            expect($this->as($user)->call($route->methods()[0], addressOf($route, $this->records)))->toBeDenied($name);
        }
    }
});

it('keeps a member from making themselves an admin', function () {
    $response = $this->as($this->member)->put(route('manage.people.update', [$this->project, $this->member]), ['family_id' => $this->family->id, 'is_admin' => true]);

    expect($response)->toBeDenied();
    expect($this->member->fresh()->manages($this->project))->toBeFalse();
});

it('keeps a member, and the admin of another project, from invalidating a run', function () {
    foreach ([$this->member, anAdminOf(aProject())] as $user) {
        $response = $this->as($user)->post(route('manage.lottery.invalidate', [$this->project, $this->run]), ['reason' => 'They did not like the outcome']);

        expect($response)->toBeDenied();
    }

    expect($this->run->fresh()->status)->toBe(LotteryRunStatus::COMPLETED);
});
