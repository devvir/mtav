<?php

namespace App\Services\Lottery;

use App\Events\InvalidPreferences;
use App\Models\Family;
use App\Models\Unit;
use App\Services\Lottery\Exceptions\LockedLotteryPreferencesException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service for handling family unit preferences.
 *
 * Ensures data integrity, non-biased default assignments, complete
 * ordering, non-repetition, consistency (all preferences belong to
 * units of the correct type for the family), removes invalid entries.
 */
class PreferencesService
{
    public function preferences(Family $family): Collection
    {
        $this->sanitizeBeforeFetch($family);

        $this->addMissingPreferences($family);

        $family->refresh();

        return $family->preferences;
    }

    /**
     * Update family unit preferences (replaces existing preferences).
     *
     * Note: $preferences must include all Units of the Family's UnitType.
     *
     * @param array<array{id: int}> $preferences
     */
    public function updatePreferences(Family $family, array $preferences): void
    {
        $this->validateBeforeUpdate($family, $preferences);

        $family->preferences()->sync(
            collect($preferences)->map(fn ($preference, $idx) => [
                'unit_id' => $preference['id'],
                'order'   => $idx + 1,
            ])->keyBy('unit_id')
        );
    }

    /**
     * Sanitize family preferences by removing invalid entries.
     *
     * Dispatches InvalidPreferences if invalid preferences are found.
     */
    protected function sanitizeBeforeFetch(Family $family): void
    {
        // Bypass any and all scopes (e.g. Units from other Projects or soft-deleted)
        $scopelessUnits = DB::select(
            'SELECT unit_id FROM unit_preferences WHERE family_id = ?',
            [$family->id]
        );

        $validUnitIds = $family->unitType->units()->pluck('id');
        $invalidUnitIds = collect($scopelessUnits)->pluck('unit_id')->diff($validUnitIds);

        if ($invalidUnitIds->isNotEmpty()) {
            DB::unprepared("
                DELETE FROM unit_preferences
                WHERE family_id = {$family->id} AND unit_id IN ({$invalidUnitIds->join(',')})
            ");

            // Ensure consumers holding a reference to this Family instance get fresh data
            $family->unsetRelation('preferences');

            InvalidPreferences::dispatch($family, $invalidUnitIds->values()->all());
        }
    }

    protected function addMissingPreferences(Family $family): void
    {
        $units = $this->missingPreferences($family);
        $startAt = max(1000, $family->preferences->max('pivot.order') ?? 0);

        $newPreferences = $units->mapWithKeys(fn (Unit $unit, int $spot) => [
            $unit->id => ['order' => $startAt + $spot + 1],
        ]);

        $family->preferences()->syncWithoutDetaching($newPreferences);
    }

    /**
     * Get remaining units (not yet preferred) in random order
     */
    protected function missingPreferences(Family $family): Collection
    {
        $family->load('preferences', 'unitType.units');

        return $family->unitType->units
            ->whereNotIn('id', $family->preferences->pluck('id'))
            ->shuffle()
            ->values();
    }

    /**
     * Validate that new preferences for a Family are complete and valid.
     *
     * @param array<array{id: int}> $preferences
     *
     * @throws InvalidArgumentException if validation fails.
     * @throws LockedLotteryPreferencesException if lottery execution has started.
     */
    protected function validateBeforeUpdate(Family $family, array $preferences): void
    {
        if (! $family->project->lottery->isPublished()) {
            throw new LockedLotteryPreferencesException();
        }

        $inputUnitIds = collect($preferences)->pluck('id');
        $candidateIds = $family->unitType->units()->pluck('id');

        if ($inputUnitIds->diff($candidateIds)->isNotEmpty()) {
            throw new InvalidArgumentException('Preferences contain one or more invalid Units.');
        }

        if ($candidateIds->diff($inputUnitIds)->isNotEmpty()) {
            throw new InvalidArgumentException('Preferences is missing one or more valid Units.');
        }
    }
}
