<?php

use App\Enums\LotteryRunStatus;
use App\Models\Activity;
use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * The demo data (dev and staging start from it): three projects, each at another stage of
 * the way, enough of everything to try each feature and to show it.
 */

it('seeds a project ranking its units, one whose lottery ran, and one being set up', function () {
    Storage::fake(Media::DISK);
    $this->seed(DatabaseSeeder::class);

    [$acacias, $ceibos, $sur] = Project::orderBy('id')->get()->all();

    expect($acacias)->name->toBe('Cooperativa Las Acacias')
        ->and($acacias->families()->count())->toBe(32)
        ->and($acacias->unitTypes()->count())->toBe(4)
        ->and($acacias->units()->whereNotNull('family_id')->count())->toBe(0)
        ->and($acacias->lottery->start_date->isFuture())->toBeTrue()
        // Drawn on its site, on three floors
        ->and($acacias->plan->items()->where('type', 'unit')->distinct()->pluck('floor')->sort()->values()->all())->toBe([0, 1, 2])
        ->and($acacias->topics()->count())->toBeGreaterThan(2);

    // Its lottery ran, by the solver: every family has its unit, and the run its record
    expect($ceibos->lotteryRuns()->sole()->status)->toBe(LotteryRunStatus::COMPLETED)
        ->and($ceibos->units()->whereNull('family_id')->count())->toBe(0);

    expect($sur->isApproved())->toBeFalse()
        ->and($sur->approval_requested_at)->not->toBeNull()
        ->and($sur->invitations()->pending()->count())->toBe(3);

    $member = User::firstWhere('email', 'member@example.com');
    expect($member->familyIn($acacias))->not->toBeNull()
        ->and($member->familyIn($ceibos))->not->toBeNull()
        // The seed is no one's doing
        ->and(Activity::count())->toBe(0);
})->group('slow');
