<?php

use App\Support\Variant;
use Illuminate\Support\Facades\Route;

/**
 * Every route belongs to a part of the app, so a variant without that part does not have it. A new
 * route is closed by this test until someone says what it belongs to.
 */

/** What every variant has */
const FOR_EVERY_VARIANT = ['/', 'ping', 'up', 'privacy', 'language', 'manifest.webmanifest', 'broadcasting/auth', 'storage/{path}', '{fallbackPlaceholder}'];

it('knows what each route belongs to', function () {
    $orphans = collect(Route::getRoutes()->getRoutes())
        ->reject(fn ($route) => in_array($route->uri(), FOR_EVERY_VARIANT, true))
        ->reject(fn ($route) => collect($route->gatherMiddleware())->contains(fn ($name) => is_string($name) && str_starts_with($name, 'module:')))
        ->map(fn ($route) => $route->uri())
        ->values();

    expect($orphans->all())->toBe([]);
});

it('has a known module and known features in every variant', function () {
    foreach (config('variants') as $name => $spec) {
        expect($spec['modules'])->each->toBeIn(['accounts', 'projects', 'platform', 'manual', 'open_lottery'], $name)
            ->and($spec['features'])->each->toBeIn(['forum', 'chat', 'gallery', 'files', 'events'], $name);
    }
});

it('leaves MTAV with everything of the app, and without Open Lottery', function () {
    expect(Variant::name())->toBe('mtav')
        ->and(Variant::has('projects'))->toBeTrue()
        ->and(Variant::has('open_lottery'))->toBeFalse();
});
