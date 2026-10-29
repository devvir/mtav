<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A shape on the plan: a unit, or anything else drawn there (buildings, streets, …).
 */
class PlanItem extends Model
{
    protected $fillable = [
        'plan_id',
        'unit_id',
        'type',
        'floor',
        'position',
        'polygon',
        'name',
        'metadata',
    ];

    protected $casts = [
        'polygon'  => 'array',
        'metadata' => 'array',
        'floor'    => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
