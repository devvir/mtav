<?php

namespace App\Models;

use App\Models\Concerns\RecordsActivity;
use App\Models\Concerns\BroadcastsProjectChanges;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The lottery participant: it ranks the units of its type and receives one of them.
 */
class Family extends Model
{
    use RecordsActivity;
    use BroadcastsProjectChanges;

    protected $fillable = [
        'project_id',
        'unit_type_id',
        'name',
        'avatar',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'memberships')
            ->withPivot('project_id', 'is_admin')
            ->withTimestamps();
    }

    /**
     * The unit the lottery assigned to this family.
     */
    public function unit(): HasOne
    {
        return $this->hasOne(Unit::class);
    }

    /**
     * Explicit rankings only. The complete ranking (every unit of the family's type)
     * comes from App\Services\Lottery\PreferencesService.
     */
    public function preferences(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'unit_preferences')
            ->withPivot('rank')
            ->orderByPivot('rank')
            ->withTimestamps();
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    /** By its name or its members' (their emails too, for whoever may see them) */
    public function scopeSearch(Builder $query, string $q, bool $byEmail = false): void
    {
        $query->where(fn (Builder $query) => $query
            ->whereLike('name', "%{$q}%")
            ->orWhereHas('members', fn (Builder $query) => $query->search($q, $byEmail)));
    }
}
