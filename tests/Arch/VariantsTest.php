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
            ->and(array_keys($spec['features']))->each->toBeIn(array_column(App\Enums\ProjectFeature::cases(), 'value'), $name);
    }
});

it('leaves MTAV with everything of the app, and without Open Lottery', function () {
    expect(Variant::name())->toBe('mtav')
        ->and(collect(['accounts', 'projects', 'platform', 'manual'])->every(Variant::has(...)))->toBeTrue()
        ->and(Variant::has('open_lottery'))->toBeFalse();
});

it('is MTAV out of the box, when the deployment says nothing or leaves it empty', function (?string $setting) {
    $before = getenv('MTAV_VARIANT');
    $set = function (string|false|null $value) {
        putenv($value === null || $value === false ? 'MTAV_VARIANT' : "MTAV_VARIANT={$value}");
        foreach (['_ENV', '_SERVER'] as $global) {
            if ($value === null || $value === false) {
                unset($GLOBALS[$global]['MTAV_VARIANT']);
            } else {
                $GLOBALS[$global]['MTAV_VARIANT'] = $value;
            }
        }
    };

    $set($setting);
    $name = (require base_path('config/variant.php'))['name'];
    $set($before);

    expect($name)->toBe('mtav');
})->with([null, '']);

it('closes nothing of MTAV but Open Lottery', function () {
    $closed = collect(Route::getRoutes())->reject(Variant::opens(...))->map(fn ($route) => $route->getName());

    expect($closed)->not->toBeEmpty()->each->toStartWith('open.');
});

it('starts MTAV projects with every feature, each of which they may turn off', function () {
    foreach (App\Enums\ProjectFeature::cases() as $feature) {
        expect(Variant::feature($feature->value))->toBe(['default' => true, 'configurable' => true], $feature->value);
    }

    expect(Variant::landing())->toBe('Welcome');
});

it('says plainly when the deployment names no variant', function () {
    config(['variant.name' => 'open-lottery']);

    expect(fn () => Variant::has('accounts'))->toThrow(RuntimeException::class, 'config/variants');
});
