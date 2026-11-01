<?php

use App\Support\Variant;
use Illuminate\Support\Facades\Route;

/**
 * Every route belongs to one part of the app, so a variant without that part does not have it. A
 * new route is closed by this test until someone says what it belongs to.
 */

/** The modules routes may name */
function knownModules(): array
{
    return ['accounts', 'projects', 'platform', 'manual', 'open_lottery'];
}

/** What a route declares of modules, leaving out what it opts out of */
function modulesOf($route): array
{
    return collect($route->gatherMiddleware())->filter(fn ($name) => is_string($name) && str_starts_with($name, 'module:'))
        ->diff($route->excludedMiddleware())
        ->map(fn (string $name) => substr($name, 7))
        ->values()->all();
}

it('knows what each route belongs to', function () {
    // What every variant has, method and address
    $forEveryVariant = [
        'GET|HEAD /', 'GET|HEAD ping', 'GET|HEAD up', 'GET|HEAD privacy', 'PUT language', 'GET|HEAD manifest.webmanifest',
        'GET|POST|HEAD broadcasting/auth', 'GET|HEAD storage/{path}', 'PUT storage/{path}', 'GET|HEAD {fallbackPlaceholder}',
    ];

    $orphans = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => ['route' => $route, 'name' => implode('|', $route->methods()) . ' ' . $route->uri()])
        ->reject(fn ($entry) => in_array($entry['name'], $forEveryVariant, true) || modulesOf($entry['route']))
        ->pluck('name')->values();

    expect($orphans->all())->toBe([]);
});

it('names modules that exist', function () {
    $unknown = collect(Route::getRoutes()->getRoutes())
        ->flatMap(fn ($route) => modulesOf($route))
        ->unique()->diff(knownModules())->values();

    expect($unknown->all())->toBe([]);
});

it('has known modules and features in every variant', function () {
    foreach (config('variants') as $name => $spec) {
        expect($spec['modules'])->each->toBeIn(knownModules(), $name)
            ->and($spec['features'])->each->toBeIn(['forum', 'chat', 'gallery', 'files', 'events'], $name);
    }
});

it('leaves MTAV with everything of the app, and without Open Lottery', function () {
    expect(Variant::name())->toBe('mtav')
        ->and(collect(['accounts', 'projects', 'platform', 'manual'])->every(Variant::has(...)))->toBeTrue()
        ->and(Variant::has('open_lottery'))->toBeFalse();
});

it('says plainly when the deployment names no variant', function () {
    config(['variant.name' => 'open-lottery']);

    expect(fn () => Variant::has('accounts'))->toThrow(RuntimeException::class, 'config/variants');
});
