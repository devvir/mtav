<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone asking to join a project from the directory. Accepted, it is gone (they are in);
 * declined, it stays a while, so that they do not ask again right away.
 */
class JoinRequest extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = [
        'project_id',
        'user_id',
        'declined_at',
    ];

    protected $casts = [
        'declined_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return ! $this->declined_at;
    }

    /** Declined so recently that they may not ask again yet */
    public function isSettled(): bool
    {
        return $this->declined_at?->isAfter(now()->subDays(config('limits.join_request_retry_days'))) ?? false;
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('declined_at');
    }
}
