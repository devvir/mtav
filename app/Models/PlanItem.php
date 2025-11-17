<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanItem extends Model
{
    protected $casts = [
        'polygon'  => 'array',
        'metadata' => 'array',
        'floor'    => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class);
    }
}
