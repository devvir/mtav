<?php

namespace App\Services\Lottery;

use App\Models\Event;
use App\Models\LotteryAudit;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\LotteryAuditType;
use App\Services\Lottery\Exceptions\LotteryExecutionException;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Service for handling lottery execution audit records.
 */
class AuditService
{
    /**
     * Initialize a Lottery execution audit and soft-deletes any existing audits
     * for the same Lottery (from previous executions).
     */
    public function init(LotteryManifest $manifest): void
    {
        // Soft-delete previous audits for this lottery
        LotteryAudit::where('lottery_id', $manifest->lotteryId)->delete();

        // Create INIT audit
        LotteryAudit::create([
            'execution_uuid' => $manifest->uuid,
            'project_id'     => $manifest->projectId,
            'lottery_id'     => $manifest->lotteryId,
            'type'           => LotteryAuditType::INIT,
            'audit'          => [
                'admin'    => Auth::user()?->only(['id', 'email']),
                'manifest' => $manifest,
            ],
        ]);
    }

    /**
     * Create an audit record for a Lottery execution.
     */
    public function audit(
        LotteryAuditType $type,
        string $execution_uuid,
        int $project_id,
        int $lottery_id,
        ExecutionResult $result
    ): LotteryAudit {
        return LotteryAudit::create([
            'execution_uuid' => $execution_uuid,
            'project_id'     => $project_id,
            'lottery_id'     => $lottery_id,
            'type'           => $type,
            'audit'          => [
                'admin'   => Auth::user()?->only(['id', 'email']),
                'picks'   => $result->picks,
                'orphans' => $result->orphans,
            ],
            'created_at' => $result->created_at,
        ]);
    }

    /**
     * Create an audit record for Lottery execution invalidation.
     */
    public function invalidate(Event $lottery): LotteryAudit
    {
        $lastExecution = $lottery->audits()->latest('created_at')->firstOrFail()->execution_uuid;

        return LotteryAudit::create([
            'execution_uuid' => $lastExecution,
            'project_id'     => $lottery->project_id,
            'lottery_id'     => $lottery->id,
            'type'           => LotteryAuditType::INVALIDATE,
            'audit'          => [
                'admin' => Auth::user()?->only(['id', 'email']),
            ],
        ]);
    }

    /**
     * Create an audit record for Lottery execution failure.
     */
    public function exception(LotteryManifest $manifest, string $errorType, Throwable $exception): LotteryAudit
    {
        return LotteryAudit::create([
            'execution_uuid' => $manifest->uuid,
            'project_id'     => $manifest->projectId,
            'lottery_id'     => $manifest->lotteryId,
            'type'           => LotteryAuditType::FAILURE,
            'audit'          => [
                'error_type'   => $errorType,
                'exception'    => get_class($exception),
                'message'      => $exception->getMessage(),
                'user_message' => $exception instanceof LotteryExecutionException
                    ? $exception->getUserMessage()
                    : __('lottery.execution_failed'),
            ],
        ]);
    }

    /**
     * Create a custom audit record with arbitrary data.
     */
    public function custom(LotteryManifest $manifest, array $data): LotteryAudit
    {
        return LotteryAudit::create([
            'execution_uuid' => $manifest->uuid,
            'project_id'     => $manifest->projectId,
            'lottery_id'     => $manifest->lotteryId,
            'type'           => LotteryAuditType::CUSTOM,
            'audit'          => [
                'admin' => Auth::user()?->only(['id', 'email']),
                ...$data,
            ],
        ]);
    }
}
