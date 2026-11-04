<?php

namespace App\Services\Lottery\DataObjects;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Represents the result of a lottery execution phase.
 *
 * @property array<int, int> $picks All assignments: participant_id => item_id
 * @property array{participants: array<int>, items: array<int>} $orphans Remaining unassigned IDs
 * @property Carbon $created_at Execution timestamp
 */
class ExecutionResult
{
    public readonly array $picks;
    public readonly array $orphans;
    public readonly Carbon $created_at;

    public function __construct(array $picks, array $orphans)
    {
        $this->picks = $this->validatePicks($picks);
        $this->orphans = $this->validateOrphans($orphans);

        $this->created_at = now();
    }

    /**
     * Validate and sanitize picks.
     *
     * @throws InvalidArgumentException if validation fails
     */
    protected function validatePicks(array $picks): array
    {
        foreach ($picks as $participantId => $itemId) {
            if (! is_numeric($participantId) || ! is_numeric($itemId)) {
                throw new InvalidArgumentException('ExecutionResult: picks must be array<int, int>.');
            }

            $sanitized[(int) $participantId] = (int) $itemId;
        }

        return $sanitized ?? [];
    }

    /**
     * Validate and sanitize orphans.
     *
     * @throws InvalidArgumentException if validation fails
     */
    protected function validateOrphans(array $orphans): array
    {
        $orphans['participants'] ??= [];
        $orphans['items'] ??= [];

        if (! is_array($orphans['participants']) || ! is_array($orphans['items'])) {
            throw new InvalidArgumentException('ExecutionResult: orphans.participants and orphans.items must be arrays.');
        }

        return [
            'participants' => $this->sanitizeIntArray($orphans['participants'], 'orphans.participants'),
            'items'        => $this->sanitizeIntArray($orphans['items'], 'orphans.items'),
        ];
    }

    /**
     * Sanitize array of numeric values to int[].
     *
     * @throws InvalidArgumentException if any value is non-numeric
     */
    protected function sanitizeIntArray(array $values, string $fieldName): array
    {
        foreach ($values as $value) {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException("ExecutionResult: {$fieldName} must be int[]. Got non-numeric value.");
            }

            $sanitized[] = (int) $value;
        }

        return $sanitized ?? [];
    }
}
