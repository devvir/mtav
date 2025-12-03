<?php

namespace App\Models;

use App\Services\Lottery\Enums\LotteryAuditType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotteryAudit extends Model
{
    use SoftDeletes;

    protected $casts = [
        'type'  => LotteryAuditType::class,
        'audit' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function lottery(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
