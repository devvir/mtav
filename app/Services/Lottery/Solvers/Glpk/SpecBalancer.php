<?php

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Balances a lottery specification with more participants than items by adding mock items,
 * so that the models can give every participant one item; whoever gets a mock item got none.
 */
class SpecBalancer
{
    /**
     * Add mock items to balance spec (more participants than items).
     *
     * Each participant ranks these fake items after every real one: with n real items, the
     * k mock items take ranks n+1 … n+k, in the same order for everyone.
     * This lets GLPK decide which participants should lose fairly.
     *
     * @return LotterySpec Balanced spec with same number of items as participants
     */
    public function addMockItems(LotterySpec $spec): LotterySpec
    {
        $mockItemsNeeded = count($spec->participants) - count($spec->items);

        $mockItems = [];
        for ($i = 1; $i <= $mockItemsNeeded; $i++) {
            $mockItems[] = 'MOCK_' . $i;
        }

        $paddedItems = array_merge($spec->items, $mockItems);
        $paddedParticipants = $this->appendMockItemsToPreferences($spec->participants, $mockItems);

        return new LotterySpec($paddedParticipants, $paddedItems);
    }

    /**
     * Check if an item ID represents a mock item (id = MOCK_*).
     */
    public function isMockItem(string|int $itemId): bool
    {
        return is_string($itemId) && str_starts_with($itemId, 'MOCK_');
    }

    /**
     * Add mock items to every participant's preferences, after all the real ones (ranks n+1 … n+k).
     *
     * @param array<int, array<int, int>> $participants Original participant preferences
     * @param array<string> $mockItems Array of mock item IDs
     *
     * @return array<int, array<int, int|string>> Updated preferences with mock items appended
     */
    protected function appendMockItemsToPreferences(array $participants, array $mockItems): array
    {
        $paddedParticipants = [];

        foreach ($participants as $participantId => $preferences) {
            $paddedParticipants[$participantId] = $preferences;

            foreach ($mockItems as $mockItemId) {
                $paddedParticipants[$participantId][] = $mockItemId;
            }
        }

        return $paddedParticipants;
    }
}
