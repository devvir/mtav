<?php

namespace App\Services\Lottery;

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Models\Event;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Exceptions\CannotExecuteLotteryException;
use App\Services\Lottery\Exceptions\InsufficientFamiliesException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\UnitFamilyMismatchException;

/**
 * Service for validating and initiating lottery execution.
 *
 * Responsibilities:
 * - Atomic reservation of lottery for execution (prevent double execution)
 * - Data integrity validation
 * - Dispatch execution event with lottery specification
 *
 * The service creates a clean boundary: everything after this point operates on
 * low-level data structures (LotterySpec) and shall be processed asynchronously.
 */
class ExecutionService
{
    /**
     * Validate lottery execution data and dispatch execution event on success.
     *
     * @param bool  $overrideCountMismatch  Bypass unit/family count mismatch validation
     *
     * @throws CannotExecuteLotteryException if lottery cannot be executed
     * @throws InsufficientFamiliesException if fewer than 2 families exist
     * @throws UnitFamilyMismatchException if units/families don't match (unless overridden)
     * @throws LotteryExecutionException if execution fails
     */
    public function execute(Event $lottery, bool $overrideCountMismatch = false): void
    {
        $this->reserveLotteryForExecution($lottery);
        $this->validateDataIntegrity($lottery);

        if (! $overrideCountMismatch) {
            $this->validateCountsConsistency($lottery->project);
        }

        $manifest = new LotteryManifest($lottery);

        LotteryExecutionTriggered::dispatch($manifest);
    }

    /**
     * Apply lottery results by assigning families to units.
     *
     * @param  array<int, int>  $picks  Array of family_id => unit_id
     */
    public function applyResults(array $picks): void
    {
        foreach ($picks as $familyId => $unitId) {
            Unit::where('id', $unitId)->update(['family_id' => $familyId]);
        }
    }

    /**
     * Reserve Lottery for execution (executing/executed) in one atomic operation:
     *
     * - validates that lottery is published and past its scheduled date
     * - validates that it has not been served for execution yet
     * - flags it as reserved for execution (by setting is_published = false)
     *
     * This protects against race conditions or any other cause of double execution.
     *
     * @throws CannotExecuteLotteryException if lottery cannot be executed
     */
    protected function reserveLotteryForExecution(Event $lottery): void
    {
        $reserved = (bool) Event::query()
            ->past()->published()->whereId($lottery->id)
            ->update(['is_published' => false]);

        if (! $reserved) {
            throw new CannotExecuteLotteryException($lottery);
        }

        $lottery->refresh();
    }

    /**
     * Validate data integrity before execution.
     *
     * @throws InsufficientFamiliesException if fewer than 2 families exist
     * @throws LotteryExecutionException if families already have units assigned
     */
    protected function validateDataIntegrity(Event $lottery): void
    {
        $families = $lottery->project->families;

        if ($families->count() < 2) {
            throw new InsufficientFamiliesException($families->count());
        }

        // Check: No families should have units assigned yet
        $assignedUnitsCount = $lottery->project->units()->whereNotNull('family_id')->exists();

        if ($assignedUnitsCount > 0) {
            throw new LotteryExecutionException(
                'Cannot execute lottery: some families already have units assigned. This indicates data corruption or a bug.'
            );
        }
    }

    /**
     * Units and families counts (per unit type) should ideally match (may be overridden).
     *
     * @throws UnitFamilyMismatchException if units/families don't match
     */
    protected function validateCountsConsistency(Project $project)
    {
        $mismatches = $project
            ->unitTypes()->withCount('units', 'families')->get()
            ->filter(fn (UnitType $ut) => $ut->units_count !== $ut->families_count)
            ->map(fn (UnitType $ut) => [
                'unit_type_id'   => $ut->id,
                'unit_type_name' => $ut->name,
                'units_count'    => $ut->units_count,
                'families_count' => $ut->families_count,
            ]);

        if ($mismatches->isNotEmpty()) {
            throw new UnitFamilyMismatchException($mismatches);
        }
    }
}
