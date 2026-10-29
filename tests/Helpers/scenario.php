<?php

use App\Enums\EventType;
use App\Enums\LotteryRunStatus;
use App\Models\Event;
use App\Models\Family;
use App\Models\LotteryRun;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PlanService;
use App\Services\ProjectService;
use Illuminate\Support\Str;

/**
 * Scenario builders for use-case tests.
 *
 * Each builder creates one entity in an explicit, valid state, the way the app
 * would. They are the only test code that knows how roles and memberships are stored.
 */

function aSuperadmin(): User
{
    return User::firstWhere('is_superadmin', true) ?? User::factory()->superadmin()->create();
}

function aUser(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/**
 * An approved project (as started by a superadmin), with its lottery and plan.
 */
function aProject(array $attributes = []): Project
{
    return app(ProjectService::class)->create(aSuperadmin(), [
        'name'        => 'Project ' . Str::random(8),
        'description' => 'A housing project',
        ...$attributes,
    ]);
}

/**
 * A project started through open sign-up, awaiting approval; its creator is its admin.
 */
function anUnapprovedProject(?User $creator = null): Project
{
    return app(ProjectService::class)->create($creator ?? aUser(), [
        'name'        => 'Project ' . Str::random(8),
        'description' => 'A housing project',
    ]);
}

function anAdminOf(Project ...$projects): User
{
    $admin = aUser();

    foreach ($projects as $project) {
        $project->memberships()->create(['user_id' => $admin->id, 'is_admin' => true]);
    }

    return $admin;
}

function aUnitType(Project $in, array $attributes = []): UnitType
{
    return $in->unitTypes()->create(['name' => 'Type ' . Str::random(8), ...$attributes]);
}

function aFamily(Project $in, ?UnitType $type = null, array $attributes = []): Family
{
    return $in->families()->create([
        'name'         => 'Family ' . Str::random(8),
        'unit_type_id' => ($type ?? aUnitType($in))->id,
        ...$attributes,
    ]);
}

function aMemberOf(Family $family, array $attributes = []): User
{
    $member = aUser($attributes);

    $family->project->memberships()->create(['user_id' => $member->id, 'family_id' => $family->id]);

    return $member;
}

function aUnit(UnitType $of, array $attributes = []): Unit
{
    $unit = $of->project->units()->create([
        'unit_type_id' => $of->id,
        'identifier'   => 'Unit ' . Str::random(8),
        ...$attributes,
    ]);

    app(PlanService::class)->place($unit);

    return $unit;
}

function anEvent(Project $in, array $attributes = []): Event
{
    return $in->events()->create([
        'type'         => EventType::ONSITE,
        'title'        => 'Event ' . Str::random(8),
        'description'  => 'An event',
        'start_date'   => now()->addWeek(),
        'is_published' => true,
        ...$attributes,
    ]);
}

/**
 * A lottery run that has started and not finished yet.
 */
function aLotteryInProgress(Project $project): LotteryRun
{
    return $project->lotteryRuns()->create([
        'uuid'       => (string) Str::uuid(),
        'status'     => LotteryRunStatus::RUNNING,
        'solver'     => 'test',
        'started_at' => now(),
        'reveal_at'  => now()->addMinutes(5),
    ]);
}

/**
 * A PNG that is a few bytes yet claims to be enormous (what opening it would cost): a
 * real header, no picture behind it
 */
function aPixelBomb(string $name = 'bomb.png', int $side = 20000): Illuminate\Http\UploadedFile
{
    $chunk = fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    $png = "\x89PNG\r\n\x1a\n"
        . $chunk('IHDR', pack('NNCCCCC', $side, $side, 8, 2, 0, 0, 0))
        . $chunk('IDAT', gzcompress(''))
        . $chunk('IEND', '');

    return Illuminate\Http\UploadedFile::fake()->createWithContent($name, $png);
}
