<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One thing that happened: a change to the project's data, or someone signing in or out.
 * Written once, never changed.
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'activity_log';

    protected $guarded = [];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('actor', "%{$q}%")
            ->orWhereLike('subject', "%{$q}%"));
    }
}
