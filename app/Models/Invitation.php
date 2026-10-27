<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An invitation to take part in a project, as an admin and/or into a family.
 * Only a hash of the emailed token is stored.
 */
class Invitation extends Model
{
    protected $fillable = [
        'project_id',
        'family_id',
        'is_admin',
        'email',
        'firstname',
        'lastname',
        'token_hash',
        'invited_by',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'is_admin'    => 'boolean',
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public static function findByToken(string $token): ?self
    {
        return static::firstWhere('token_hash', static::hashToken($token));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return ! $this->accepted_at && $this->expires_at->isFuture();
    }

    public function scopePending(Builder $query): void
    {
        $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }
}
