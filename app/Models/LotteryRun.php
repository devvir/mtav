<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use App\Enums\LotteryRunStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One execution of a project's lottery. A completed run is final unless an admin
 * invalidates it, giving a reason that every member of the project can read.
 */
class LotteryRun extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = [
        'uuid',
        'project_id',
        'status',
        'solver',
        'options',
        'started_by',
        'started_at',
        'reveal_at',
        'result',
        'finished_at',
        'failure',
        'invalidated_by',
        'invalidated_at',
        'invalidation_reason',
    ];

    protected $casts = [
        'status'         => LotteryRunStatus::class,
        'options'        => 'array',
        'result'         => 'array',
        'reveal_at'      => 'datetime',
        'started_at'     => 'datetime',
        'finished_at'    => 'datetime',
        'invalidated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function invalidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(LotteryAudit::class)->orderBy('id');
    }
}
