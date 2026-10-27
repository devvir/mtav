<?php

namespace App\Models;

use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A permanent record of what a lottery run considered and decided.
 */
class LotteryAudit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'lottery_run_id',
        'type',
        'audit',
        'created_at',
    ];

    protected $casts = [
        'type'  => LotteryAuditType::class,
        'audit' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(LotteryRun::class, 'lottery_run_id');
    }
}
