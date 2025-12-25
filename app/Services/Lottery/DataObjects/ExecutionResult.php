<?php

namespace App\Services\Lottery\DataObjects;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Represents the result of a lottery execution phase.
 *
 * @property array<int, int> $picks All assignments: family_id => unit_id
 * @property array{families: array<int>, units: array<int>} $orphans Remaining unassigned IDs
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
        foreach ($picks as $familyId => $unitId) {
            if (! is_numeric($familyId) || ! is_numeric($unitId)) {
                throw new InvalidArgumentException("ExecutionResult: picks must be array<int, int>.");
            }

            $sanitized[(int) $familyId] = (int) $unitId;
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
        $orphans['families'] ??= [];
        $orphans['units'] ??= [];

        if (! is_array($orphans['families']) || ! is_array($orphans['units'])) {
            throw new InvalidArgumentException("ExecutionResult: orphans.families and orphans.units must be arrays.");
        }

        return [
            'families' => $this->sanitizeIntArray($orphans['families'], 'orphans.families'),
            'units'    => $this->sanitizeIntArray($orphans['units'], 'orphans.units'),
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
