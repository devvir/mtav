<?php

// Copilot - Pending review

use App\Models\Event;
use App\Models\LotteryAudit;
use App\Models\Member;
use App\Services\Lottery\AuditService;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use Illuminate\Support\Facades\Auth;

uses()->group('Unit.Lottery');

beforeEach(function () {
    $this->service = app(AuditService::class);
    $this->manifest = mockManifest(lotteryId: 1, projectId: 1);
});

describe('AuditService', function () {
    test('init creates INIT audit and soft-deletes previous audits', function () {
        // Create existing audits
        for ($i = 0; $i < 3; $i++) {
            LotteryAudit::create([
                'execution_uuid' => 'old-uuid-' . $i,
                'lottery_id'     => $this->manifest->lotteryId,
                'project_id'     => $this->manifest->projectId,
                'type'           => LotteryAuditType::CUSTOM,
                'audit'          => [],
            ]);
        }

        expect(LotteryAudit::where('lottery_id', $this->manifest->lotteryId)->count())->toBe(3);

        $this->service->init($this->manifest);

        // Old audits soft-deleted
        expect(LotteryAudit::where('lottery_id', $this->manifest->lotteryId)->withTrashed()->count())->toBe(4);
        expect(LotteryAudit::where('lottery_id', $this->manifest->lotteryId)->count())->toBe(1);

        // New audit created
        $audit = LotteryAudit::where('lottery_id', $this->manifest->lotteryId)->first();
        expect($audit->type)->toBe(LotteryAuditType::INIT);
        expect($audit->execution_uuid)->toBe($this->manifest->uuid);
        expect($audit->audit['manifest'])->toBeArray();
    });

    test('audit creates record with correct type and data', function () {
        $admin = Member::factory()->create();
        Auth::login($admin);

        $result = new ExecutionResult(
            picks: [1 => 1, 2 => 2],
            orphans: ['families' => [3], 'units' => []]
        );

        $audit = $this->service->audit(
            LotteryAuditType::GROUP_EXECUTION,
            $this->manifest->uuid,
            $this->manifest->projectId,
            $this->manifest->lotteryId,
            $result
        );

        expect($audit)->toBeInstanceOf(LotteryAudit::class);
        expect($audit->type)->toBe(LotteryAuditType::GROUP_EXECUTION);
        expect($audit->execution_uuid)->toBe($this->manifest->uuid);
        expect($audit->audit['picks'])->toBe([1 => 1, 2 => 2]);
        expect($audit->audit['orphans'])->toBe(['families' => [3], 'units' => []]);
        expect($audit->audit['admin'])->toHaveKey('id', $admin->id);
        expect($audit->created_at->timestamp)->toBe($result->created_at->timestamp);
    });

    test('audit includes authenticated user data', function () {
        $admin = Member::factory()->create();
        Auth::login($admin);

        $result = new ExecutionResult(picks: [1 => 1], orphans: ['families' => [], 'units' => []]);

        $audit = $this->service->audit(
            LotteryAuditType::GROUP_EXECUTION,
            $this->manifest->uuid,
            $this->manifest->projectId,
            $this->manifest->lotteryId,
            $result
        );

        expect($audit->audit['admin'])->toBe([
            'id'    => $admin->id,
            'email' => $admin->email,
        ]);
    });

    test('invalidate creates INVALIDATE audit with last execution uuid', function () {
        $lottery = Event::factory()->create(['project_id' => $this->manifest->projectId]);

        // Create previous audit
        LotteryAudit::create([
            'execution_uuid' => 'previous-uuid',
            'lottery_id'     => $lottery->id,
            'project_id'     => $lottery->project_id,
            'type'           => LotteryAuditType::CUSTOM,
            'audit'          => [],
        ]);

        $admin = Member::factory()->create();
        Auth::login($admin);

        $audit = $this->service->invalidate($lottery);

        expect($audit->type)->toBe(LotteryAuditType::INVALIDATE);
        expect($audit->execution_uuid)->toBe('previous-uuid');
        expect($audit->lottery_id)->toBe($lottery->id);
        expect($audit->audit['admin'])->toHaveKey('id');
    });

    test('exception creates FAILURE audit with error details', function () {
        $exception = new Exception('Something went wrong');

        $audit = $this->service->exception(
            $this->manifest,
            'runtime_error',
            $exception
        );

        expect($audit->type)->toBe(LotteryAuditType::FAILURE);
        expect($audit->execution_uuid)->toBe($this->manifest->uuid);
        expect($audit->audit['error_type'])->toBe('runtime_error');
        expect($audit->audit['exception'])->toBe(Exception::class);
        expect($audit->audit['message'])->toBe('Something went wrong');
        expect($audit->audit['user_message'])->toBe(__('lottery.execution_failed'));
    });

    test('exception handles LotteryExecutionException with default message', function () {
        $exception = new LotteryExecutionException('Technical error');

        $audit = $this->service->exception(
            $this->manifest,
            'validation_error',
            $exception
        );

        expect($audit->audit['exception'])->toBe(LotteryExecutionException::class);
        expect($audit->audit['message'])->toBe('Technical error');
        expect($audit->audit['user_message'])->toBe(__('lottery.execution_failed'));
    });

    test('custom creates CUSTOM audit with merged data', function () {
        $admin = Member::factory()->create();
        Auth::login($admin);

        $customData = [
            'task'     => 'glpk_distribution',
            'status'   => 'success',
            'result'   => ['distribution' => [1 => 1]],
            'metadata' => ['timeout_ms' => 1000],
        ];

        $audit = $this->service->custom($this->manifest, $customData);

        expect($audit->type)->toBe(LotteryAuditType::CUSTOM);
        expect($audit->execution_uuid)->toBe($this->manifest->uuid);
        expect($audit->audit['admin'])->toHaveKey('id', $admin->id);
        expect($audit->audit['task'])->toBe('glpk_distribution');
        expect($audit->audit['status'])->toBe('success');
        expect($audit->audit['result'])->toBe(['distribution' => [1 => 1]]);
        expect($audit->audit['metadata'])->toBe(['timeout_ms' => 1000]);
    });

    test('custom merges admin data with custom data', function () {
        $admin = Member::factory()->create();
        Auth::login($admin);

        $audit = $this->service->custom($this->manifest, [
            'custom_field' => 'value',
        ]);

        // Admin added automatically
        expect($audit->audit)->toHaveKeys(['admin', 'custom_field']);
        expect($audit->audit['admin'])->toHaveKey('email');
        expect($audit->audit['custom_field'])->toBe('value');
    });

    test('audit methods work without authenticated user', function () {
        Auth::logout();

        // init
        $this->service->init($this->manifest);
        $audit = LotteryAudit::where('lottery_id', $this->manifest->lotteryId)->first();
        expect($audit->audit['admin'])->toBeNull();

        // audit
        $result = new ExecutionResult(picks: [1 => 1], orphans: ['families' => [], 'units' => []]);
        $audit = $this->service->audit(
            LotteryAuditType::GROUP_EXECUTION,
            $this->manifest->uuid,
            $this->manifest->projectId,
            $this->manifest->lotteryId,
            $result
        );
        expect($audit->audit['admin'])->toBeNull();

        // custom
        $audit = $this->service->custom($this->manifest, ['test' => true]);
        expect($audit->audit['admin'])->toBeNull();
    });
});
