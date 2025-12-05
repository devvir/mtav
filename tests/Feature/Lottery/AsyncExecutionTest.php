<?php

// Copilot - Pending review

use App\Models\Event;
use App\Models\Family;
use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Support\Facades\Config;

uses()->group('Feature.Lottery.Async');

describe('Async Lottery Execution Tests', function () {
    test('successful lottery execution creates audit trail in database', function () {
        Config::set('lottery.default', 'test');
        Config::set('queue.default', 'sync'); // Use sync for test reliability

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

    test('solver failure creates FAILURE audit and releases lottery', function () {
        // Force GlpkException by pointing to non-existent glpsol
        Config::set('lottery.solvers.glpk.glpsol_path', '/nonexistent/glpsol');
        Config::set('lottery.default', 'glpk');
        Config::set('queue.default', 'sync');

        setCurrentProject(4);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true, 'deleted_at' => null]);

        // Set minimal preferences
        $family = Family::find(16);
        $family->preferences()->sync([
            13 => ['order' => 1],
            14 => ['order' => 2],
        ]);

        // Execute lottery (should fail gracefully)
        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Verify FAILURE audit exists
        $failureAudit = $lottery->audits()->where('type', 'failure')->first();

        expect($failureAudit)->not->toBeNull();
        expect($failureAudit->audit)->toHaveKey('exception');
        expect($failureAudit->audit['exception'])->toContain('GlpkException');
        expect($failureAudit->audit)->toHaveKey('user_message');
        expect($failureAudit->audit['user_message'])->toBeString();

        // Verify lottery is released (can retry)
        $lottery->refresh();
        expect($lottery->is_published)->toBeTrue();
        expect($lottery->deleted_at)->toBeNull();
    });

    test('failed execution can be retried and creates new audit trail', function () {
        Config::set('queue.default', 'sync');

        setCurrentProject(4);
        $lottery = Event::find(13);
        $lottery->update(['is_published' => true, 'deleted_at' => null]);

        // Set preferences
        $family = Family::find(16);
        $family->preferences()->sync([
            13 => ['order' => 1],
            14 => ['order' => 2],
        ]);

        // First attempt: Force failure
        Config::set('lottery.default', 'glpk');
        Config::set('lottery.solvers.glpk.glpsol_path', '/nonexistent');

        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        $firstUuid = $lottery->audits()
            ->where('type', 'init')
            ->orderBy('created_at', 'desc')
            ->first()
            ->execution_uuid;

        // Verify first attempt failed
        $firstFailure = $lottery->audits()->where('execution_uuid', $firstUuid)->where('type', 'failure');
        expect($firstFailure)->toExist();

        // Second attempt: Fix config and retry
        Config::set('lottery.default', 'test');
        $lottery->refresh();
        expect($lottery->is_published)->toBeTrue(); // Should be released after failure

        $this->submitFormToRoute(['lottery.execute', $lottery->id], asAdmin: 13);

        // Get second execution UUID
        $allInits = $lottery->audits()
            ->where('type', LotteryAuditType::INIT->value)
            ->orderBy('created_at', 'desc')
            ->get();

        // Should have at least one INIT (possibly two if both completed)
        expect($allInits->count())->toBeGreaterThanOrEqual(1);

        // Get the most recent INIT audit
        $secondInit = $allInits->first();
        $secondUuid = $secondInit->execution_uuid;

        // If there are two INITs, verify different UUIDs
        if ($allInits->count() >= 2) {
            expect($firstUuid)->not->toBe($secondUuid);

            // Verify first attempt audits are soft-deleted
            $firstAttemptAudits = $lottery->audits()
                ->withTrashed()
                ->where('execution_uuid', $firstUuid)
                ->get();

            expect($firstAttemptAudits->count())->toBeGreaterThan(0);
            expect($firstAttemptAudits->every(fn ($a) => $a->trashed()))->toBeTrue();
        }

        // Verify second attempt succeeded (lottery completed)
        expect($lottery->fresh()->trashed())->toBeTrue();
    });

    test('audit records share execution UUID', function () {
        Config::set('lottery.default', 'test');
        Config::set('queue.default', 'sync');

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
        Config::set('lottery.default', 'test');
        Config::set('queue.default', 'sync');

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
