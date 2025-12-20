// Copilot - Pending review
<?php

use App\Models\Event;
use App\Models\Family;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\ExecutionService;

uses()->group('Feature.Lottery');

beforeEach(function () {
    // Use fast test solver for retry testing
    config()->set('lottery.default', 'test');
    config()->set('queue.default', 'sync');
});

describe('Lottery Execution Audit Trail', function () {
    test('single execution creates audit group with consistent UUID', function () {
        setCurrentProject(4); // Balanced project from universe.sql
        $lottery = Event::find(13); // Already published and not deleted from fixture

        // Set preferences for families
        $preferences = [
            16 => [13, 14],
            22 => [14, 13],
            17 => [15, 16],
            27 => [16, 15],
            18 => [17, 18],
            28 => [18, 17],
        ];

        foreach ($preferences as $familyId => $unitIds) {
            $family = Family::find($familyId);
            $syncData = [];
            foreach ($unitIds as $order => $unitId) {
                $syncData[$unitId] = ['order' => $order + 1];
            }
            $family->preferences()->sync($syncData);
        }

        // Execute lottery
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Get execution UUID from INIT audit
        $initAudit = $lottery->audits()
            ->where('type', LotteryAuditType::INIT->value)
            ->first();

        expect($initAudit)->not->toBeNull();
        $executionUuid = $initAudit->execution_uuid;

        // All audits from this execution should share the same UUID
        $allAudits = $lottery->audits()->get();
        $allUuids = $allAudits->pluck('execution_uuid')->unique();

        expect($allUuids->count())->toBe(1);
        expect($allUuids->first())->toBe($executionUuid);

        // Verify execution completed
        expect($lottery->fresh()->trashed())->toBeTrue();
    });

    test('multiple executions create separate audit groups with different UUIDs', function () {
        setCurrentProject(4);
        $lottery = Event::find(13); // Already published and not deleted from fixture

        // Set preferences
        $preferences = [
            16 => [13, 14],
            22 => [14, 13],
            17 => [15, 16],
            27 => [16, 15],
            18 => [17, 18],
            28 => [18, 17],
        ];

        foreach ($preferences as $familyId => $unitIds) {
            $family = Family::find($familyId);
            $syncData = [];
            foreach ($unitIds as $order => $unitId) {
                $syncData[$unitId] = ['order' => $order + 1];
            }
            $family->preferences()->sync($syncData);
        }

        // First execution
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        $firstInit = $lottery->audits()
            ->withTrashed()
            ->where('type', LotteryAuditType::INIT->value)
            ->orderBy('created_at', 'asc')
            ->first();

        expect($firstInit)->not->toBeNull();
        $firstUuid = $firstInit->execution_uuid;

        // Verify first execution completed
        expect($lottery->fresh()->trashed())->toBeTrue();

        // Invalidate lottery to allow retry (restores, republishes, clears assignments)
        $executionService = app(ExecutionService::class);
        $lottery->refresh(); // refresh to get updated state
        $executionService->invalidate($lottery);

        // Verify lottery is ready for retry
        $lottery->refresh();
        expect($lottery->is_published)->toBeTrue();
        expect($lottery->deleted_at)->toBeNull();

        // Reset preferences for second run
        foreach ($preferences as $familyId => $unitIds) {
            $family = Family::find($familyId);
            $syncData = [];
            foreach ($unitIds as $order => $unitId) {
                $syncData[$unitId] = ['order' => $order + 1];
            }
            $family->preferences()->sync($syncData);
        }

        // Second execution
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Get all INITs including soft-deleted ones
        $allInits = $lottery->audits()
            ->withTrashed()
            ->where('type', LotteryAuditType::INIT->value)
            ->orderBy('created_at', 'asc')
            ->get();

        // Should have exactly 2 INIT audits
        expect($allInits->count())->toBe(2);

        // Second execution must have different UUID
        $secondUuid = $allInits->last()->execution_uuid;
        expect($firstUuid)->not->toBe($secondUuid);

        // Verify first execution audits are soft-deleted
        $firstAudits = $lottery->audits()
            ->withTrashed()
            ->where('execution_uuid', $firstUuid)
            ->get();

        expect($firstAudits->every(fn ($a) => $a->trashed()))->toBeTrue();

        // Verify second execution completed
        expect($lottery->fresh()->trashed())->toBeTrue();
    });
});

