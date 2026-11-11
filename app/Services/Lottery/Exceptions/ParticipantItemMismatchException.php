<?php

namespace App\Services\Lottery\Exceptions;

use Illuminate\Support\Collection;

/**
 * Some unit types have more units than families, or fewer: some units stay free, or some
 * families receive none. The admin confirms it, naming each type and its counts.
 */
class ParticipantItemMismatchException extends LotteryRequiresConfirmationException
{
    /** What the admin sends back to run it all the same */
    public const OPTION = 'mismatch-allowed';

    /**
     * @param Collection<int, array{group_id: int, group_name: string, items_count: int, participants_count: int}> $mismatches
     */
    public function __construct(Collection $mismatches)
    {
        parent::__construct(self::OPTION, $this->describe($mismatches));
    }

    private function describe(Collection $mismatches): string
    {
        $details = $mismatches->map(fn (array $mismatch) => __(
            $mismatch['items_count'] > $mismatch['participants_count'] ? 'lottery.mismatch_excess_units' : 'lottery.mismatch_insufficient_units',
            [
                'unit_type' => "\"{$mismatch['group_name']}\"",
                'units'     => $mismatch['items_count'],
                'families'  => $mismatch['participants_count'],
            ],
        ))->join(', ');

        return __('lottery.unit_family_mismatch_intro') . ' ' . $details;
    }
}
