<?php

namespace App\Services\Lottery;

use App\Models\LotteryAudit;
use App\Services\Lottery\DataObjects\LotteryManifest;
use App\Services\Lottery\Enums\LotteryAuditType;

/**
 * Writes the audit trail of lottery runs, kept as long as the project is (deleting it
 * for good takes them along): re-running a lottery after an invalidation adds a new run
 * with its own trail.
 */
class AuditService
{
    public function record(LotteryManifest|int $run, LotteryAuditType $type, array $audit): LotteryAudit
    {
        return LotteryAudit::create([
            'lottery_run_id' => $run instanceof LotteryManifest ? $run->runId : $run,
            'type'           => $type,
            'audit'          => $audit,
            'created_at'     => now(),
        ]);
    }

    /**
     * Free-form records from solvers (e.g. GLPK task details).
     */
    public function custom(LotteryManifest $manifest, array $data): LotteryAudit
    {
        return $this->record($manifest, LotteryAuditType::CUSTOM, $data);
    }
}
