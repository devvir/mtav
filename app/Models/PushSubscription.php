<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A browser or device where someone allowed notifications: where its push service takes
 * them (`endpoint`), and its keys
 */
class PushSubscription extends Model
{
    protected $fillable = ['endpoint', 'public_key', 'auth_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
