<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use App\Observers\UnitObserver;
use App\Relations\BelongsToThrough;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([UnitObserver::class])]
class Unit extends Model
{
    use ProjectScope;
    use SoftDeletes;

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

    public function planItem(): BelongsTo
    {
        return $this->belongsTo(PlanItem::class);
    }

    public function plan(): BelongsToThrough
    {
        return $this->belongsToThrough(Plan::class, PlanItem::class);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('identifier');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query
            ->whereLike('identifier', "%{$q}%")
            ->orWhereHas(
                'type',
                fn (Builder $query) => $query->whereLike('description', "%{$q}%")
            )
            ->orWhereHas(
                'family',
                fn (Builder $query) => $query->whereLike('name', "%{$q}%")
            );
    }
}
