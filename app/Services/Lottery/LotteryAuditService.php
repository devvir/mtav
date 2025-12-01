<?php

namespace App\Services\Lottery;

use App\Models\LotteryAudit;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\Enums\ExecutionType;

/**
 * Service for handling lottery execution audit records.
 */
class LotteryAuditService
{
    /**
     * Create an audit record for a lottery execution.
     */
    public function audit(
        ExecutionType $type,
        string $execution_uuid,
        int $project_id,
        int $lottery_id,
        ExecutionResult $result
    ): LotteryAudit {
        return LotteryAudit::create([
            'execution_uuid' => $execution_uuid,
            'project_id'     => $project_id,
            'lottery_id'     => $lottery_id,
            'execution_type' => $type,
            'audit'          => [
                'picks'   => $result->picks,
                'orphans' => $result->orphans,
            ],
            'created_at' => $result->created_at,
        ]);
    }
}
