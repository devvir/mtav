<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** An invitation to a support account: a superadmin, on an address not in the app yet */
class SupportInvitation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public static function findByToken(string $token): ?self
    {
        return static::firstWhere('token_hash', Invitation::hashToken($token));
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isPending(): bool
    {
        return ! $this->accepted_at && $this->expires_at->isFuture();
    }
}
