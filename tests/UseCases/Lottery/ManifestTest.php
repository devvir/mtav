<?php

use App\Models\Family;
use App\Models\LotteryRun;
use App\Models\Project;
use App\Models\Unit;
use App\Services\Lottery\PreferencesService;
use App\Services\LotteryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * What a lottery decides from, read all at once: ties depend on the order families reach the
 * solver, so reading it faster must not change a thing in it, order included.
 */

/** Each family read on its own, in full: how the manifest was built before it read them at once */
function manifestReadOneByOne(Project $project): array
{
    $units = $project->units()->get(['id', 'unit_type_id'])->groupBy('unit_type_id');

    return $project->families()->get()->groupBy('unit_type_id')
        ->map(fn ($families, $typeId) => [
            'participants' => $families->mapWithKeys(fn ($family) => [$family->id => rankingReadOnItsOwn($family)])->all(),
            'items'        => ($units[$typeId] ?? collect())->pluck('id')->all(),
        ])
        ->all();
}

function rankingReadOnItsOwn(Family $family): array
{
    $units = Unit::where('unit_type_id', $family->unit_type_id)->get()->keyBy('id');

    $ranked = DB::table('unit_preferences')
        ->where('family_id', $family->id)
        ->whereIn('unit_id', $units->keys())
        ->orderBy('rank')
        ->pluck('unit_id');

    $unranked = $units->keys()->diff($ranked)
        ->sortBy(fn (int $unitId) => hash('sha256', config('app.key') . ":{$family->id}:{$unitId}"));

    return $ranked->concat($unranked)->map(fn (int $unitId) => $units[$unitId])->values()->pluck('id')->all();
}

function manifestOf(Project $project): array
{
    $run = new LotteryRun(['uuid' => (string) Str::uuid()]);
    $run->id = 1;
    $run->setRelation('project', $project);

    return app(LotteryService::class)->manifest($run)->data;
}

/**
 * A project as they come: types in a scrambled order, families joining them turn about, rankings
 * whole, partial, reversed and absent, a unit that left its type after being ranked, a type with
 * no units, an archived family, and another project's rows around it.
 */
function aMessyProject(int $seed): Project
{
    mt_srand($seed);
    $project = aProject();
    $types = collect(range(1, 4))->map(fn () => aUnitType($project));
    $empty = aUnitType($project);
    $units = collect(range(1, 30))->map(fn () => aUnit($types[mt_rand(0, 3)]));
    $elsewhere = aProject();
    aFamily($elsewhere, aUnitType($elsewhere));

    foreach (range(1, 24) as $n) {
        $type = $n % 7 === 0 ? $empty : $types[mt_rand(0, 3)];
        $family = aFamily($project, $type, ['name' => $n % 5 === 0 ? 'Same name' : "Family {$n}"]);
        $own = $units->where('unit_type_id', $type->id)->shuffle($seed + $n)->values();

        match ($n % 4) {
            0       => app(PreferencesService::class)->rank($family, $own->pluck('id')->all()),
            1       => app(PreferencesService::class)->rank($family, $own->take(2)->pluck('id')->all()),
            2       => app(PreferencesService::class)->rank($family, $own->reverse()->pluck('id')->all()),
            default => null,
        };

        if ($n % 11 === 0) {
            $family->delete();
        }
    }

    // A unit moved to another type, its old rankings left behind
    $units->last()->update(['unit_type_id' => $types->first(fn ($type) => $type->id !== $units->last()->unit_type_id)->id]);

    return $project;
}

it('is what reading each family on its own gave, down to the order', function (int $seed) {
    $project = aMessyProject($seed);

    $before = manifestReadOneByOne($project);
    $now = manifestOf($project);

    expect($now)->toBe($before)
        ->and(json_encode($now))->toBe(json_encode($before))
        ->and(array_keys($now))->toBe(array_keys($before));

    foreach ($before as $type => $group) {
        expect(array_keys($now[$type]['participants']))->toBe(array_keys($group['participants']));
    }

    expect(collect($now)->flatMap(fn ($group) => array_keys($group['participants']))->all())->not->toBeEmpty();
})->with([1, 2, 3, 4, 5]);

it('gives each family what the ranking page shows it', function () {
    $project = aMessyProject(6);
    $shown = fn (Family $family) => app(PreferencesService::class)->ranking($family)->pluck('id')->all();

    foreach ($project->families()->get() as $family) {
        expect(manifestOf($project)[$family->unit_type_id]['participants'][$family->id])->toBe($shown($family));
    }
});

it('reads as much for many families as for a few', function () {
    $queries = function (int $families) {
        $project = aProject();
        $type = aUnitType($project);
        collect(range(1, 6))->each(fn () => aUnit($type));
        collect(range(1, $families))->each(fn () => aFamily($project, $type));

        DB::flushQueryLog();
        DB::enableQueryLog();
        manifestOf($project);
        $read = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $read;
    };

    expect($queries(20))->toBe($queries(2));
});

it('goes through a run the same way it always did', function () {
    $project = aMessyProject(7);
    $before = manifestReadOneByOne($project);
    $run = aLotteryInProgress($project);

    expect(app(LotteryService::class)->manifest($run)->data)->toBe($before);
});

it('leaves a family with no units of its type with an empty ranking', function () {
    $project = aProject();
    $family = aFamily($project, aUnitType($project));

    expect(manifestOf($project)[$family->unit_type_id]['participants'][$family->id])->toBe([]);
});
