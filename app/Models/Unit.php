<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
