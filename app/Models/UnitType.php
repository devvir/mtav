<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitType extends Model
{
    use ProjectScope;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }
}
