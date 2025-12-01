<?php

namespace App\Models;

use App\Services\Lottery\Enums\ExecutionType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryAudit extends Model
{
    protected $casts = [
        'execution_type' => ExecutionType::class,
        'audit'          => 'array',
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
