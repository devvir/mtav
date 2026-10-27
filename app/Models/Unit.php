<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A concrete dwelling. Unassigned until the lottery gives it to a family.
 */
class Unit extends Model
{
    protected $fillable = [
        'project_id',
        'unit_type_id',
        'family_id',
        'identifier',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'unit_type_id');
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function planItem(): HasOne
    {
        return $this->hasOne(PlanItem::class);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('identifier');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('identifier', "%{$q}%")
            ->orWhereHas('type', fn (Builder $query) => $query->whereLike('name', "%{$q}%"))
            ->orWhereHas('family', fn (Builder $query) => $query->whereLike('name', "%{$q}%")));
    }
}
