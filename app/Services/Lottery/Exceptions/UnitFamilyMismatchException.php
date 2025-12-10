<?php

namespace App\Services\Lottery\Exceptions;

use Illuminate\Support\Collection;

/**
 * Exception thrown when units and families don't match per unit type.
 *
 * Extends LotteryRequiresConfirmationException to signal that the mismatch
 * can be bypassed with explicit admin confirmation (via 'mismatch-allowed' option).
 * Generates detailed user-facing message about which unit types have mismatches.
 */
class UnitFamilyMismatchException extends LotteryRequiresConfirmationException
{
    /**
     * @param  Collection<array{unit_type_id: int, unit_type_name: string, units_count: int, families_count: int}>  $mismatches
     */
    public function __construct(Collection $mismatches)
    {
        parent::__construct(
            option: 'mismatch-allowed',
            message: $this->buildUserMessage($mismatches)
        );
    }

    /**
     * Build the detailed user-facing message about mismatches.
     */
    protected function buildUserMessage(Collection $mismatches): string
    {
        $details = $mismatches->map(function ($mismatch) {
            $unitTypeName = $mismatch['unit_type_name'];
            $unitsCount = $mismatch['units_count'];
            $familiesCount = $mismatch['families_count'];

            if ($unitsCount > $familiesCount) {
                return __('lottery.mismatch_excess_units', [
                    'unit_type' => "\"{$unitTypeName}\"",
                    'units'     => $unitsCount,
                    'families'  => $familiesCount,
                ]);
            }

            return __('lottery.mismatch_insufficient_units', [
                'unit_type' => "\"{$unitTypeName}\"",
                'units'     => $unitsCount,
                'families'  => $familiesCount,
            ]);
        })->join(", ");

        return __('lottery.unit_family_mismatch_intro') . " " . $details;
    }
}
