<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Plan extends Model
{
    use ProjectScope;

    protected $with = [
        'items',
    ];

    protected $casts = [
        'polygon' => 'array',
        'width'   => 'decimal:2',
        'height'  => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }

    public function units(): HasManyThrough
    {
        return $this->hasManyThrough(Unit::class, PlanItem::class);
    }
}
