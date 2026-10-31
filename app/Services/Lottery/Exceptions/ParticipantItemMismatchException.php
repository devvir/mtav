<?php

namespace App\Services\Lottery\Exceptions;

use Illuminate\Support\Collection;

/**
 * Exception thrown when items and participants don't match per group.
 *
 * Extends LotteryRequiresConfirmationException to signal that the mismatch
 * can be bypassed with explicit admin confirmation (via 'mismatch-allowed' option).
 * Generates detailed user-facing message about which groups have mismatches.
 */
class ParticipantItemMismatchException extends LotteryRequiresConfirmationException
{
    /**
     * @param  Collection<array{group_id: int, group_name: string, items_count: int, participants_count: int}>  $mismatches
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
            $groupName = $mismatch['group_name'];
            $itemsCount = $mismatch['items_count'];
            $participantsCount = $mismatch['participants_count'];

            if ($itemsCount > $participantsCount) {
                return __('lottery.mismatch_excess_units', [
                    'unit_type' => "\"{$groupName}\"",
                    'units'     => $itemsCount,
                    'families'  => $participantsCount,
                ]);
            }

            return __('lottery.mismatch_insufficient_units', [
                'unit_type' => "\"{$groupName}\"",
                'units'     => $itemsCount,
                'families'  => $participantsCount,
            ]);
        })->join(", ");

        return __('lottery.unit_family_mismatch_intro') . " " . $details;
    }
}
