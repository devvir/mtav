<?php

namespace App\Models;

use App\Models\Concerns\BroadcastsProjectChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A group of equivalent units: each family receives a unit of its own type.
 */
class UnitType extends Model
{
    use BroadcastsProjectChanges;

    protected $fillable = [
        'project_id',
        'name',
        'description',
    ];

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

    public function isInUse(): bool
    {
        return $this->units()->exists() || $this->families()->exists();
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }
}
