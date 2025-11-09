<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Unit extends Model
{
    use ProjectScope;

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

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('identifier');
    }

    public function scopeSearch(Builder $query, string $q): void
    {
        $query->whereLike('identifier', "%$q%");
    }
}
