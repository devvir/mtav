<?php

namespace App\Services\Lottery\DataObjects;

use InvalidArgumentException;

/**
 * Lottery specification for executing a single lottery.
 *
 * Represents the data needed to execute one lottery: families, units,
 * and their preferences. This is the atomic unit of lottery execution -
 * solvers process one LotterySpec at a time.
 */
class LotterySpec
{
    public readonly array $families;
    public readonly array $units;

    public function __construct(array $families, array $units)
    {
        $this->units = $this->validateUnits($units);
        $this->families = $this->validateFamilies($families);
    }

    /**
     * Get the number of families in this lottery.
     */
    public function familyCount(): int
    {
        return count($this->families);
    }

    /**
     * Get the number of units in this lottery.
     */
    public function unitCount(): int
    {
        return count($this->units);
    }

    /**
     * Check if this is a balanced lottery (same number of families and units).
     */
    public function isBalanced(): bool
    {
        return $this->familyCount() === $this->unitCount();
    }

    /**
     * Filter the spec to only include specified units and/or families.
     */
    public function filter(?array $unitIds = null, ?array $familyIds = null): self
    {
        $units = isset($unitIds)        /** Remove non-included Units, if $unitIds was passed */
            ? array_values(array_filter($this->units, fn ($id) => in_array($id, $unitIds)))
            : $this->units;

        $families = isset($familyIds)   /** Remove non-included Families, if $familyIds was passed */
            ? array_filter($this->families, fn ($id) => in_array($id, $familyIds), ARRAY_FILTER_USE_KEY)
            : $this->families;

        $families = array_map(          /** Make sure all preferences still point to non-removed Units */
            fn ($ids) => array_values(array_filter($ids, fn ($id) => in_array($id, $units))),
            $families
        );

        return new self(families: $families, units: $units);
    }

    /**
     * Validate and sanitize families array.
     *
     * @param  array<int|string, array<int|string>>  $families  family_id => [unit_id, ...]
     * @throws InvalidArgumentException if validation fails
     */
    protected function validateFamilies(array $families): array
    {
        $sanitized = [];

        foreach ($families as $familyId => $preferences) {
            if (! is_string($familyId) && ! is_int($familyId)) {
                throw new InvalidArgumentException('LotterySpec: family IDs must be string or int.');
            }

            if (! is_array($preferences)) {
                throw new InvalidArgumentException("LotterySpec: preferences for family {$familyId} must be an array.");
            }

            // Check for duplicate preferences
            if (count($preferences) !== count(array_unique($preferences))) {
                throw new InvalidArgumentException("LotterySpec: family {$familyId} has duplicate preferences.");
            }

            // Validate each preference is a valid unit ID
            foreach ($preferences as $unitId) {
                if (! in_array($unitId, $this->units, true)) {
                    throw new InvalidArgumentException("LotterySpec: family {$familyId} has preference for unit {$unitId} which is not in the units array.");
                }
            }

            $sanitized[$familyId] = $preferences;
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize units array.
     *
     * @param  array<int|string>  $units
     * @throws InvalidArgumentException if validation fails
     */
    protected function validateUnits(array $units): array
    {
        foreach ($units as $unitId) {
            if (! is_string($unitId) && ! is_int($unitId)) {
                throw new InvalidArgumentException('LotterySpec: unit IDs must be string or int.');
            }
        }

        // Check for duplicates
        if (count($units) !== count(array_unique($units))) {
            throw new InvalidArgumentException('LotterySpec: units array contains duplicate values.');
        }

        return $units;
    }

    /**
     * Sanitize array of unit IDs (numeric or mock string IDs).
     *
     * @throws InvalidArgumentException if any value is invalid
     * @deprecated No longer used - kept for backward compatibility
     */
    protected function sanitizeUnitIdArray(array $values, string $fieldName): array
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
