<?php

// Copilot - Pending review

use App\Models\LotteryAudit;
use App\Services\Lottery\DataObjects\LotterySpec;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Solvers\Glpk\Glpk;

uses()->group('Feature.Lottery.Glpk');

beforeEach(function () {
    config()->set('logging.default', 'null');
    config()->set('lottery.default', 'glpk');
    config()->set('lottery.solvers.glpk.config.glpk_phase1_timeout', 5);
    config()->set('lottery.solvers.glpk.config.glpk_phase1_max_size', 10);
    config()->set('lottery.solvers.glpk.config.timeout', 10);
});

describe('GLPK Custom Audits', function () {
    test('distributeUnits creates custom audit with correct GLPK strategy data', function () {
        $manifest = mockManifest();
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);

        $glpk = app(Glpk::class);
        $glpk->distributeUnits($manifest, $spec);

        // Check audit was created
        $audit = LotteryAudit::where('execution_uuid', $manifest->uuid)->first();
        expect($audit)->not->toBeNull();
        expect($audit->type)->toBe(LotteryAuditType::CUSTOM);

        $data = $audit->audit;

        // Should have task field with correct value
        expect($data)->toHaveKey('task');
        expect($data['task'])->toBeIn(['glpk_distribution', 'hybrid_distribution']);

        // Should have status
        expect($data)->toHaveKey('status');
        expect($data['status'])->toBe('success');

        // Should have result with distribution
        expect($data)->toHaveKey('result');
        expect($data['result'])->toHaveKey('distribution');
        expect($data['result']['distribution'])->toBeArray();

        // Should have metadata with timing and artifacts
        expect($data)->toHaveKey('metadata');
        expect($data['metadata'])->toHaveKey('timeout_ms');
        expect($data['metadata'])->toHaveKey('artifacts');
    });

    test('distributeUnits with large spec creates audit with hybrid_distribution task', function () {
        $manifest = mockManifest();

        // Large spec (11 families >= 10 threshold)
        $units = range(1, 11);

        for ($i = 1; $i <= count($units); $i++) {
            $families[$i] = [...$units];
            shuffle($families[$i]);
        }

        $spec = new LotterySpec(families: $families, units: $units);

        $glpk = app(Glpk::class);
        $glpk->distributeUnits($manifest, $spec);

        $audit = LotteryAudit::where('execution_uuid', $manifest->uuid)->first();
        expect($audit)->not->toBeNull();

        $data = $audit->audit;
        expect($data['task'])->toBe('hybrid_distribution');
        expect($data['metadata'])->toHaveKey('iterations');
        expect($data['metadata'])->toHaveKey('feasible_steps');
    });

    test('distributeUnits with small spec creates audit with glpk_distribution task', function () {
        $manifest = mockManifest();
        $spec = new LotterySpec(
            families: [1 => [1, 2, 3], 2 => [2, 3, 1], 3 => [3, 1, 2]],
            units: [1, 2, 3]
        );

        $glpk = app(Glpk::class);
        $glpk->distributeUnits($manifest, $spec);

        $audit = LotteryAudit::where('execution_uuid', $manifest->uuid)->first();
        expect($audit)->not->toBeNull();

        $data = $audit->audit;
        expect($data['task'])->toBe('glpk_distribution');
        expect($data['metadata'])->toHaveKey('phase1');
        expect($data['metadata'])->toHaveKey('phase2');
        expect($data['metadata']['phase1'])->toHaveKey('min_satisfaction');
        expect($data['metadata']['phase2'])->toHaveKey('min_satisfaction');
    });

    test('identifyWorstUnits creates custom audit with worst_units_pruning task', function () {
        $manifest = mockManifest();
        $spec = new LotterySpec(
            families: [1 => [1, 2, 3, 4], 2 => [2, 3, 1, 4]],
            units: [1, 2, 3, 4]
        );

        $glpk = app(Glpk::class);
        $glpk->identifyWorstUnits($manifest, $spec);

        $audit = LotteryAudit::where('execution_uuid', $manifest->uuid)->first();
        expect($audit)->not->toBeNull();

        $data = $audit->audit;
        expect($data['task'])->toBe('worst_units_pruning');
        expect($data['status'])->toBe('success');
        expect($data['result'])->toHaveKey('worst_units');
        expect($data['result']['worst_units'])->toBeArray();
    });

    test('audit includes GLPK artifacts in metadata', function () {
        $manifest = mockManifest();
        $spec = new LotterySpec(families: [1 => [1, 2], 2 => [2, 1]], units: [1, 2]);

        $glpk = app(Glpk::class);
        $glpk->distributeUnits($manifest, $spec);

        $audit = LotteryAudit::where('execution_uuid', $manifest->uuid)->first();
        expect($audit)->not->toBeNull();

        $artifacts = $audit->audit['metadata']['artifacts'];

        // Should have artifacts
        expect($artifacts)->toBeArray();
        expect($artifacts)->not->toBeEmpty();

        // Each artifact should be filename => content
        foreach ($artifacts as $filename => $content) {
            expect($filename)->toBeString();
            expect($content)->toBeString();

            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            expect($ext)->toBeIn(['mod', 'dat', 'sol']);
        }
    });
});
