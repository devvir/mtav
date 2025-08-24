<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
