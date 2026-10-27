<?php

namespace App\Services\Lottery;

use App\Models\Family;
use App\Models\Unit;
use App\Services\Lottery\Exceptions\LotteryLockedException;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A family's ranking of the units it competes for (those of its type).
 *
 * Families rank as many units as they like; the rest follow in an order that is
 * random but fixed for each family, so that families who do not rank are not all
 * pointed at the same units. Rankings of units that left the family's type (or the
 * project) are simply ignored.
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

        $unranked = $units->keys()->diff($ranked)
            ->sortBy(fn (int $unitId) => $this->defaultOrder($family, $unitId));

        return new EloquentCollection($ranked->concat($unranked)->map(fn (int $unitId) => $units[$unitId])->values());
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
        if ($family->project->lotteryLocked()) {
            throw new LotteryLockedException();
        }

        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));
        $valid = Unit::where('unit_type_id', $family->unit_type_id)->whereIn('id', $unitIds)->pluck('id');

        if ($valid->count() !== count($unitIds)) {
            throw ValidationException::withMessages(['preferences' => __('validation.preferences_foreign_units')]);
        }

        DB::transaction(function () use ($family, $unitIds) {
            $family->preferences()->sync(
                collect($unitIds)->mapWithKeys(fn (int $unitId, int $index) => [$unitId => ['rank' => $index + 1]])
            );
        });
    }

    private function defaultOrder(Family $family, int $unitId): string
    {
        return hash('sha256', config('app.key') . ":{$family->id}:{$unitId}");
    }
}
