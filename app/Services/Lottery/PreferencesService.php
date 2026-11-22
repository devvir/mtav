<?php

namespace App\Services\Lottery;

use App\Models\Activity;
use App\Models\Family;
use App\Models\Unit;
use App\Services\ActivityLog;
use App\Services\Lottery\Exceptions\LotteryLockedException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A family's ranking of the units it may receive (those of its type).
 *
 * Families rank as many units as they like; the rest follow in an order that is
 * random but fixed for each family, so that families who do not rank are not all
 * pointed at the same units. Rankings of units that left the family's type (or the
 * project) are simply ignored.
 *
 * Each change is noted in the activity log (`ranked`), for the lottery's record: who left the
 * family's ranking as what, and when. A sitting of changes by one person counts as one.
 */
class PreferencesService
{
    /**
     * Every unit of the family's type, most wanted first.
     *
     * @return EloquentCollection<int, Unit>
     */
    public function ranking(Family $family): EloquentCollection
    {
        $units = Unit::where('unit_type_id', $family->unit_type_id)->get()->keyBy('id');

        $ranked = DB::table('unit_preferences')
            ->where('family_id', $family->id)
            ->whereIn('unit_id', $units->keys())
            ->orderBy('rank')
            ->pluck('unit_id');

        return new EloquentCollection($this->complete($family, $units->keys(), $ranked)->map(fn (int $unitId) => $units[$unitId])->all());
    }

    /**
     * What ranking() says of each of these families, in unit ids, reading them all at once:
     * how long this takes does not grow with families times units.
     *
     * @param Collection<int, Family> $families
     *
     * @return array<int, list<int>>  Family id => every unit of its type, most wanted first
     */
    public function rankings(Collection $families): array
    {
        $units = Unit::whereIn('unit_type_id', $families->pluck('unit_type_id')->unique())->get(['id', 'unit_type_id'])
            ->groupBy('unit_type_id')
            ->map(fn (EloquentCollection $units) => $units->pluck('id'));

        $ranked = DB::table('unit_preferences')
            ->whereIn('family_id', $families->modelKeys())
            ->orderBy('rank')
            ->get(['family_id', 'unit_id'])
            ->groupBy('family_id');

        return $families->mapWithKeys(function (Family $family) use ($units, $ranked) {
            $ofItsType = $units[$family->unit_type_id] ?? collect();
            $itsRanking = ($ranked[$family->id] ?? collect())->pluck('unit_id')->intersect($ofItsType)->values();

            return [$family->id => $this->complete($family, $ofItsType, $itsRanking)->all()];
        })->all();
    }

    /**
     * Replace the family's ranking. Units not listed keep following, in default order.
     *
     * @param array<int> $unitIds  Most wanted first
     *
     * @throws ValidationException if a unit is not of the family's type
     * @throws LotteryLockedException once the lottery has run (or is running)
     */
    public function rank(Family $family, array $unitIds): void
    {
        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));

        // Checked and written with the project held still: a run starting meanwhile has this
        // ranking in its manifest, or this ranking is refused
        $family->project->holdingLottery(function () use ($family, $unitIds) {
            if ($family->project->lotteryLocked()) {
                throw new LotteryLockedException();
            }

            $valid = Unit::where('unit_type_id', $family->unit_type_id)->whereIn('id', $unitIds)->pluck('id');

            if ($valid->count() !== count($unitIds)) {
                throw ValidationException::withMessages(['preferences' => __('validation.preferences_foreign_units')]);
            }

            $before = DB::table('unit_preferences')->where('family_id', $family->id)->orderBy('rank')->pluck('unit_id')->map(intval(...))->all();

            $family->preferences()->sync(
                collect($unitIds)->mapWithKeys(fn (int $unitId, int $index) => [$unitId => ['rank' => $index + 1]])
            );

            $before === $unitIds || $this->noteChange($family, $before, $unitIds);
        });
    }

    /**
     * The change, for the record: a new entry, or the sitting's entry brought up to date when
     * the same person saved the same family's ranking a little while ago
     */
    private function noteChange(Family $family, array $before, array $after): void
    {
        $sitting = Activity::where('action', 'ranked')
            ->where('subject_type', 'family')
            ->where('subject_id', $family->id)
            ->where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subMinutes((int) config('limits.ranking_change_minutes')))
            ->latest('id')
            ->first();

        if ($sitting && Auth::id()) {
            $sitting->update(['changes' => ['ranking' => [$sitting->changes['ranking'][0] ?? $before, $after], 'until' => now()->toIso8601String()]]);

            return;
        }

        ActivityLog::record('ranked', $family, ['ranking' => [$before, $after], 'until' => now()->toIso8601String()]);
    }

    /** The units ranked, then the rest in the family's own order */
    private function complete(Family $family, Collection $units, Collection $ranked): Collection
    {
        $unranked = $units->diff($ranked)->sortBy(fn (int $unitId) => $this->defaultOrder($family, $unitId));

        return $ranked->concat($unranked)->values();
    }

    private function defaultOrder(Family $family, int $unitId): string
    {
        return hash('sha256', config('app.key') . ":{$family->id}:{$unitId}");
    }
}
