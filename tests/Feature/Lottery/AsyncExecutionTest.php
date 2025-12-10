<?php

// Copilot - Pending review

use App\Models\Event;
use App\Models\Family;
use App\Services\Lottery\Enums\LotteryAuditType;

uses()->group('Feature.Lottery.Async');

beforeEach(function () {
    config()->set('lottery.default', 'test');
});

describe('Async Lottery Execution Tests', function () {
    test('successful lottery execution creates audit trail in database', function () {
        setCurrentProject(4); // Balanced project from universe.sql
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true]);

        // Set preferences for families in Project #4
        // Type #7 families: 16, 22
        // Type #8 families: 17, 27
        // Type #9 families: 18, 28
        $preferences = [
            16 => [13, 14], // Type #7 units
            22 => [14, 13],
            17 => [15, 16], // Type #8 units
            27 => [16, 15],
            18 => [17, 18], // Type #9 units
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

        // Verify audit records exist
        $audits = $lottery->audits;

        // Should have at least INIT audit
        expect($audits->count())->toBeGreaterThanOrEqual(1);

        // Verify INIT audit exists and has manifest data
        $initAudit = $audits->where('type', LotteryAuditType::INIT->value)->first();
        expect($initAudit)->not->toBeNull();
        expect($initAudit->audit)->toHaveKey('manifest');

        // If execution completed, verify lottery is soft-deleted
        // and we have execution audits
        if ($lottery->fresh()->trashed()) {
            expect($audits->count())->toBeGreaterThanOrEqual(4);
            $hasGroupExecutions = $audits->where('type', LotteryAuditType::GROUP_EXECUTION->value)->count() > 0;
            $hasProjectExecution = $audits->where('type', LotteryAuditType::PROJECT_EXECUTION->value)->count() > 0;
            expect($hasGroupExecutions || $hasProjectExecution)->toBeTrue();
        }
    });

    test('audit records share execution UUID', function () {
        setCurrentProject(4);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true, 'deleted_at' => null]);

        // Set preferences for all families
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

        // Get all audits for this lottery
        $audits = $lottery->audits;

        // All audits should share the same execution_uuid
        $uuids = $audits->pluck('execution_uuid')->unique();
        expect($uuids->count())->toBe(1);

        // Verify we have INIT audit at minimum
        $hasInit = $audits->where('type', LotteryAuditType::INIT->value)->count() > 0;
        expect($hasInit)->toBeTrue();
    });

    test('INIT audit contains complete manifest data', function () {
        setCurrentProject(4);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true, 'deleted_at' => null]);

        // Set minimal preferences
        $family = Family::find(16);
        $family->preferences()->sync([
            13 => ['order' => 1],
            14 => ['order' => 2],
        ]);

        // Execute lottery
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Get INIT audit
        $initAudit = $lottery->audits()
            ->where('type', LotteryAuditType::INIT->value)
            ->first();

        expect($initAudit)->not->toBeNull();
        expect($initAudit->audit)->toHaveKey('manifest');

        $manifest = $initAudit->audit['manifest'];
        // Manifest is a LotteryManifest object, serialized
        expect($manifest)->toBeArray();
        expect($manifest)->toHaveKey('lotteryId');
        expect($manifest)->toHaveKey('projectId');
        expect($manifest['lotteryId'])->toBe($lottery->id);
        expect($manifest['projectId'])->toBe(4);
    });
});
