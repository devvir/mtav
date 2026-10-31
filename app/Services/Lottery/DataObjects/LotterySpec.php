<?php

namespace App\Services\Lottery\DataObjects;

use InvalidArgumentException;

/**
 * Lottery specification for executing a single lottery.
 *
 * Represents the data needed to execute one lottery: participants, items,
 * and their preferences. This is the smallest piece of lottery execution -
 * solvers process one LotterySpec at a time.
 */
class LotterySpec
{
    public readonly array $participants;
    public readonly array $items;

    public function __construct(array $participants, array $items)
    {
        $this->items = $this->validateItems($items);
        $this->participants = $this->validateParticipants($participants);
    }

    /**
     * Get the number of participants in this lottery.
     */
    public function participantCount(): int
    {
        return count($this->participants);
    }

    /**
     * Get the number of items in this lottery.
     */
    public function itemCount(): int
    {
        return count($this->items);
    }

    /**
     * Check if this is a balanced lottery (same number of participants and items).
     */
    public function isBalanced(): bool
    {
        return $this->participantCount() === $this->itemCount();
    }

    /**
     * Filter the spec to only include specified items and/or participants.
     */
    public function filter(?array $itemIds = null, ?array $participantIds = null): self
    {
        $items = isset($itemIds)                /** Remove non-included Items, if $itemIds was passed */
            ? array_values(array_filter($this->items, fn ($id) => in_array($id, $itemIds)))
            : $this->items;

        $participants = isset($participantIds)  /** Remove non-included Participants, if $participantIds was passed */
            ? array_filter($this->participants, fn ($id) => in_array($id, $participantIds), ARRAY_FILTER_USE_KEY)
            : $this->participants;

        $participants = array_map(              /** Make sure all preferences still point to non-removed Items */
            fn ($ids) => array_values(array_filter($ids, fn ($id) => in_array($id, $items))),
            $participants
        );

        return new self(participants: $participants, items: $items);
    }

    /**
     * Validate and sanitize participants array.
     *
     * @param  array<int|string, array<int|string>>  $participants  participant_id => [item_id, ...]
     * @throws InvalidArgumentException if validation fails
     */
    protected function validateParticipants(array $participants): array
    {
        $sanitized = [];

        foreach ($participants as $participantId => $preferences) {
            if (! is_string($participantId) && ! is_int($participantId)) {
                throw new InvalidArgumentException('LotterySpec: participant IDs must be string or int.');
            }

            if (! is_array($preferences)) {
                throw new InvalidArgumentException("LotterySpec: preferences for participant {$participantId} must be an array.");
            }

            // Check for duplicate preferences
            if (count($preferences) !== count(array_unique($preferences))) {
                throw new InvalidArgumentException("LotterySpec: participant {$participantId} has duplicate preferences.");
            }

            // Validate each preference is a valid item ID
            foreach ($preferences as $itemId) {
                if (! in_array($itemId, $this->items, true)) {
                    throw new InvalidArgumentException("LotterySpec: participant {$participantId} has preference for item {$itemId} which is not in the items array.");
                }
            }

            $sanitized[$participantId] = $preferences;
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize items array.
     *
     * @param  array<int|string>  $items
     * @throws InvalidArgumentException if validation fails
     */
    protected function validateItems(array $items): array
    {
        foreach ($items as $itemId) {
            if (! is_string($itemId) && ! is_int($itemId)) {
                throw new InvalidArgumentException('LotterySpec: item IDs must be string or int.');
            }
        }

        // Check for duplicates
        if (count($items) !== count(array_unique($items))) {
            throw new InvalidArgumentException('LotterySpec: items array contains duplicate values.');
        }

        return $items;
    }

    /**
     * Sanitize array of item IDs (numeric or mock string IDs).
     *
     * @throws InvalidArgumentException if any value is invalid
     * @deprecated No longer used - kept for backward compatibility
     */
    protected function sanitizeItemIdArray(array $values, string $fieldName): array
    {
        foreach ($values as $value) {
            // Allow numeric IDs or string IDs starting with 'MOCK_'
            if (is_numeric($value)) {
                $sanitized[] = (int) $value;
            } elseif (is_string($value) && str_starts_with($value, 'MOCK_')) {
                $sanitized[] = $value;
            } else {
                throw new InvalidArgumentException("LotterySpec: {$fieldName} must contain only numeric IDs or MOCK_* strings. Got invalid value.");
            }
        }

        return $sanitized ?? [];
    }
}
