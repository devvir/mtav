<?php

use App\Support\Variant;
use App\Support\VariantFile;
use Illuminate\Support\Facades\Route;

/**
 * Every route belongs to one part of the app, so a variant without that part does not have it. A
 * new route is closed by this test until someone says what it belongs to. And a deployment's
 * variant file is read for what it says, refused for what the app does not know.
 */

/** What a route declares of modules, leaving out what it opts out of */
function modulesOf($route): array
{
    return collect($route->gatherMiddleware())->filter(fn ($name) => is_string($name) && str_starts_with($name, 'module:'))
        ->diff($route->excludedMiddleware())
        ->map(fn (string $name) => substr($name, 7))
        ->values()->all();
}

/** A variant file with the given content, for one test */
function variantFile(string $yaml): string
{
    $path = tempnam(sys_get_temp_dir(), 'variant-') . '.yaml';
    file_put_contents($path, $yaml);

    return $path;
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
        ->unique()->diff(VariantFile::MODULES)->values();

    expect($unknown->all())->toBe([]);
});

it('ships variant files the app reads, each with a landing page and a privacy page', function () {
    foreach (shippedVariants() as $file) {
        $variant = VariantFile::load($file);

        expect($variant['file'])->toBe($file)
            ->and(resource_path("js/pages/{$variant['landing']}.tsx"))->toBeFile()
            ->and(resource_path("views/legal/en/{$variant['privacy']}"))->toBeFile();
    }
});

it('names each deployment\'s variant after an environment the workflows deploy', function () {
    $deployed = [];
    foreach (glob(base_path('.github/workflows/*.yml')) as $workflow) {
        $text = file_get_contents($workflow);
        preg_match_all('/deploy\/ci\/deploy deploy ([a-z][a-z0-9-]*)/', $text, $named);
        preg_match_all('/options:\s*\[([^\]]*)\]/', $text, $choices);
        $deployed = [...$deployed, ...$named[1], ...array_map('trim', explode(',', implode(',', $choices[1])))];
    }

    foreach (glob(base_path('variants/*.yaml')) as $file) {
        expect(basename($file, '.yaml'))->toBeIn(array_filter($deployed));
    }
});

it('reads the environment\'s own variant, carried by the image, and MTAV where it has none', function (?string $environment, array $modules) {
    $before = getenv('MTAV_ENV');
    putenv($environment === null ? 'MTAV_ENV' : "MTAV_ENV={$environment}");
    $_ENV['MTAV_ENV'] = $_SERVER['MTAV_ENV'] = $environment;

    $variant = require base_path('config/variant.php');

    putenv($before === false ? 'MTAV_ENV' : "MTAV_ENV={$before}");
    unset($_ENV['MTAV_ENV'], $_SERVER['MTAV_ENV']);

    expect($variant['modules'])->toBe($modules);
})->with([
    'sorteo'     => ['sorteo', ['open_lottery']],
    'staging'    => ['staging', ['accounts', 'projects', 'platform', 'manual']],
    'none'       => [null, ['accounts', 'projects', 'platform', 'manual']],
    'not a name' => ['../sorteo', ['accounts', 'projects', 'platform', 'manual']],
]);

it('is MTAV out of the box: with no file, with an empty file, and as the example documents it', function () {
    $mtav = VariantFile::defaults();

    expect(VariantFile::load(null))->toBe($mtav)
        ->and(VariantFile::load(base_path('no-such-variant.yaml')))->toBe($mtav)
        ->and(VariantFile::load($empty = variantFile('')))->toBe([...$mtav, 'file' => $empty])
        ->and(VariantFile::load($example = base_path('variant.example.yaml')))->toBe([...$mtav, 'file' => $example]);
});

it('keeps MTAV\'s value for a key the file leaves out, and replaces whole the one it gives', function () {
    $variant = VariantFile::load($file = variantFile("modules: [projects, accounts]\nfeatures:\n  forum: { default: false, configurable: true }\n"));

    expect($variant['modules'])->toBe(['projects', 'accounts'])
        ->and($variant['features'])->toBe(['forum' => ['default' => false, 'configurable' => true]])
        ->and($variant['landing'])->toBe('Welcome')
        ->and($variant['privacy'])->toBe('privacy.html')
        ->and($variant['file'])->toBe($file);
});

it('refuses a file that names what the app does not know, saying what and where', function (string $yaml, string $complaint) {
    $file = variantFile($yaml);

    expect(fn () => VariantFile::load($file))->toThrow(RuntimeException::class, $complaint)
        ->and(fn () => VariantFile::load($file))->toThrow(RuntimeException::class, $file);
})->with([
    'an unknown key'      => ["theme: dark\n", 'names a key the app does not know: "theme"'],
    'an unknown module'   => ["modules: [accounts, billing]\n", 'names a module the app does not have: "billing"'],
    'an unknown feature'  => ["features:\n  wiki: { default: true, configurable: true }\n", 'names a feature the app does not have: "wiki"'],
    'a half-said feature' => ["features:\n  forum: { default: true }\n", 'must give the feature "forum" as { default: true|false, configurable: true|false }'],
    'modules not a list'  => ["modules: accounts\n", 'must give modules as a list'],
    'an empty landing'    => ["landing: ''\n", 'must give landing as a non-empty string'],
    'not YAML'            => ["modules: [accounts\n", 'is not valid YAML'],
]);

it('leaves MTAV with everything of the app, and without Open Lottery', function () {
    expect(Variant::file())->toBeNull()
        ->and(collect(['accounts', 'projects', 'platform', 'manual'])->every(Variant::has(...)))->toBeTrue()
        ->and(Variant::has('open_lottery'))->toBeFalse();
});

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

it('is Open Lottery, and nothing else, from its file', function () {
    variant('sorteo');

    expect(Variant::has('open_lottery'))->toBeTrue()
        ->and(collect(['accounts', 'projects', 'platform', 'manual'])->some(Variant::has(...)))->toBeFalse()
        ->and(Variant::choices())->toBe([])
        ->and(Variant::landing())->toBe('Open/Landing')
        ->and(collect(Route::getRoutes())->filter(Variant::opens(...))->flatMap(modulesOf(...))->unique()->values()->all())->toBe(['open_lottery']);
});
