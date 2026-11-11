<?php

namespace App\Services\Lottery\Solvers\Glpk;

use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\DataObjects\LotterySpec;

/**
 * Balances lottery specifications to handle unequal participant/item counts.
 *
 * Handles two scenarios:
 * - More items than participants: Prunes worst items to achieve balance
 * - More participants than items: Adds mock items to achieve balance
 */
class SpecBalancer
{
    public function __construct(protected Glpk $glpk)
    {
    }

    /**
     * Remove worst items to balance spec (more items than participants).
     *
     * Strategy: two-step process to maintain max-min fairness:
     *  1. Heuristic pruning: Find minimal preference depth where enough items exist
     *  2.a. If exact match (#picked-items = #participants), we're done
     *  2.b. Otherwise, use GLPK to prune worst items among the remaining candidates
     *
     * @return LotterySpec Balanced spec with same number of items as participants
     */
    public function pruneWorstItems(LotteryManifest $manifest, LotterySpec $spec): LotterySpec
    {
        // Step 1: Heuristic pruning - find minimal set of candidate items
        $itemIds = $this->collectTopPreferredItems($spec);

        // Step 2: If there are still more items than preferences, use GLPK to prune further
        if (count($itemIds) > count($spec->participants)) {
            $candidateSpec = $spec->filter(itemIds: $itemIds);
            $worstRemainingItems = $this->glpk->identifyWorstItems($manifest, $candidateSpec);

            $itemIds = array_diff($itemIds, $worstRemainingItems);
        }

        return $spec->filter(itemIds: $itemIds);
    }

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
     * Collect items that appear in participants' top preferences.
     *
     * Algorithm: Start with top-1 choices, collect unique items. If fewer than M participants,
     * expand to top-2, then top-3, etc. Stop when we have at least M unique items.
     *
     * Result: Returns Px items where M ≤ Px ≤ 2M-1
     *
     * @return array<int> Array of item IDs
     */
    protected function collectTopPreferredItems(LotterySpec $spec): array
    {
        $itemsCount = count($spec->items);
        $participantCount = count($spec->participants);

        $depth = 0;
        $uniqueItems = [];

        while (count($uniqueItems) < $participantCount && $depth < $itemsCount) {
            // Collect preferences of level $depth from each participant
            foreach ($spec->participants as $preferences) {
                $uniqueItems[$preferences[$depth]] = true;
            }

            $depth++;
        }

        $result = array_keys($uniqueItems);
        sort($result);

        return $result;
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
