<?php

namespace App\Services\Lottery\Exceptions;

use Exception;
use Illuminate\Support\Collection;

/**
 * Exception thrown when units and families don't match per unit type.
 *
 * Can be bypassed with force parameter to allow execution with mismatches.
 * Generates detailed message about which unit types have mismatches.
 */
class UnitFamilyMismatchException extends Exception
{
    /**
     * @param  Collection<array{unit_type_id: int, unit_type_name: string, units_count: int, families_count: int}>  $mismatches
     */
    public function __construct(
        private readonly Collection $mismatches
    ) {
        $details = $mismatches->map(fn ($m) =>
            "Unit type '{$m['unit_type_name']}' has {$m['units_count']} units for {$m['families_count']} families"
        )->join('; ');

        parent::__construct("Unit/family mismatch detected: {$details}");
    }

    public function getMismatches(): Collection
    {
        return $this->mismatches;
    }

    /**
     * Generate user-facing message with mismatch details.
     */
    public function getUserMessage(): string
    {
        $details = $this->mismatches->map(function ($mismatch) {
            $unitTypeName = $mismatch['unit_type_name'];
            $unitsCount = $mismatch['units_count'];
            $familiesCount = $mismatch['families_count'];

            if ($unitsCount > $familiesCount) {
                return __('lottery.mismatch_excess_units', [
                    'unit_type' => "\"{$unitTypeName}\"",
                    'units' => $unitsCount,
                    'families' => $familiesCount,
                ]);
            }

            return __('lottery.mismatch_insufficient_units', [
                'unit_type' => "\"{$unitTypeName}\"",
                'units' => $unitsCount,
                'families' => $familiesCount,
            ]);
        })->join(", ");

        return __('lottery.unit_family_mismatch_intro')." ".$details;
    }
}
