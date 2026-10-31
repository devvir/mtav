<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsProjectChanges;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Someone asking to join a family. A family is a real thing: the person either is in it or is
 * not. Accepted, it is gone (they are in); declined, it stays, and they cannot ask that family
 * again until one of its members removes the decline.
 */
class FamilyJoinRequest extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = ['project_id', 'family_id', 'user_id', 'declined_at'];

    protected $casts = [
        'declined_at' => 'datetime',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('declined_at');
    }

    public function scopeDeclined(Builder $query): void
    {
        $query->whereNotNull('declined_at');
    }
}
