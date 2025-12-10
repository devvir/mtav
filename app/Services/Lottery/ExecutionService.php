<?php

namespace App\Services\Lottery;

use App\Events\Lottery\LotteryExecutionTriggered;
use App\Models\Event;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Exceptions\LotteryRequiresConfirmationException;
use App\Services\Lottery\Exceptions\CannotExecuteLotteryException;
use App\Services\Lottery\Exceptions\InsufficientFamiliesException;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use App\Services\Lottery\Exceptions\UnitFamilyMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

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
    public function __construct(
        private AuditService $auditService,
    ) {
        // ...
    }

    /**
     * Validate lottery execution data and dispatch execution event on success.
     *
     * @param array<string> $options  User-confirmed options
     *
     * @throws CannotExecuteLotteryException if lottery cannot be executed
     * @throws InsufficientFamiliesException if fewer than 2 families exist
     * @throws LotteryRequiresConfirmationException if admin confirmation is needed
     * @throws LotteryExecutionException if execution fails
     */
    public function execute(Event $lottery, array $options = []): void
    {
        try {
            $uuid = $this->reserveLotteryForExecution($lottery);
            $manifest = new LotteryManifest($uuid, $lottery, $options);

            $this->validateDataIntegrity($lottery);
            $this->validateCountsConsistency($lottery->project, $options);

            $this->auditService->init($manifest);
        } catch (Throwable $e) {
            $this->cancelExecutionReservation($lottery);
            throw $e;
        }

        // TODO : investigate and fix issue with deferred Queue driver in local
        //        deferred events aren´t being triggered from previously deferred code
        app()->environment('local')
            ? defer(fn () => LotteryExecutionTriggered::dispatch($manifest))
            : LotteryExecutionTriggered::dispatch($manifest);
    }

    /**
     * Apply lottery results by assigning families to units.
     *
     * @param  array<int, int>  $picks  Array of family_id => unit_id
     */
    public function applyResults(int $lotteryId, array $picks): void
    {
        foreach ($picks as $familyId => $unitId) {
            Unit::where('id', $unitId)->update(['family_id' => $familyId]);
        }

        Event::whereId($lotteryId)->delete(); // soft-delete
    }

    /**
     * Invalidate a completed lottery execution.
     *
     *  - Restores the soft-deleted lottery
     *  - Republishes it (is_published = true)
     *  - Removes all family assignments from units
     *  - Creates an INVALIDATE audit record
     */
    public function invalidate(Event $lottery): void
    {
        DB::transaction(function () use ($lottery) {
            // Restore the lottery (un-soft-delete)
            $lottery->restore();

            // Make it published again
            $lottery->update(['is_published' => true]);

            // Remove all unit assignments
            $lottery->project->units()->update(['family_id' => null]);

            // Create invalidation audit record
            $this->auditService->invalidate($lottery);
        });
    }

    /**
     * Cancel the execution reservation, making the lottery available again.
     */
    public function cancelExecutionReservation(Event|int $lotteryOrId): void
    {
        $lottery = model($lotteryOrId, Event::class, withTrashed: true);

        $lottery->update(['is_published' => true]);
        $lottery->refresh();
    }

    /**
     * Reserve Lottery for execution (executing/executed) in one atomic operation:
     *
     *  - validates that lottery is published and past its scheduled date
     *  - validates that it has not been served for execution yet
     *  - flags it as reserved for execution (by setting is_published = false)
     *  - generates unique identifier (UUID) for this execution
     *
     * This protects against race conditions or any other cause of double execution.
     *
     * @throws CannotExecuteLotteryException if lottery cannot be executed
     * @return string The execution UUID
     */
    protected function reserveLotteryForExecution(Event $lottery): string
    {
        $reserved = (bool) Event::whereId($lottery->id)
            ->published()
            ->past(implicitDuration: 0)
            ->update(['is_published' => false]);

        if (! $reserved) {
            throw new CannotExecuteLotteryException($lottery);
        }

        $lottery->refresh();

        return Str::uuid()->toString();
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
     * @param array<string> $options  User-confirmed options
     *
     * @throws LotteryRequiresConfirmationException if units/families don't match (unless allowed)
     */
    protected function validateCountsConsistency(Project $project, array $options = [])
    {
        if (in_array('mismatch-allowed', $options)) {
            return;
        }

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
