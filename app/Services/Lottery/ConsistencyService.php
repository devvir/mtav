<?php

namespace App\Services\Lottery;

use App\Events\InvalidPreferencesEvent;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Service for validating and sanitizing family unit preferences.
 *
 * Ensures data integrity by checking that all preferences belong to units
 * of the correct type for the family, removing invalid entries, and
 * reordering remaining preferences.
 */
class ConsistencyService
{
    /**
     * Sanitize family preferences by removing invalid entries.
     *
     * Dispatches InvalidPreferencesEvent if invalid preferences are found.
     */
    public function sanitizeBeforeFetch(Family $family): void
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

            InvalidPreferencesEvent::dispatch($family, $invalidUnitIds->values()->all());
        }
    }

    /**
     * Validate that new preferences for a Family are complete and valid.
     *
     * @param array<array{id: int}> $preferences
     *
     * @throws InvalidArgumentException if validation fails.
     */
    public function validateBeforeUpdate(Family $family, array $preferences): void
    {
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
