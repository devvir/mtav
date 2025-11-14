<?php

namespace App\Models;

use App\Models\Concerns\ProjectScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Family extends Model
{
    use ProjectScope;

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function media(): HasManyThrough
    {
        return $this->hasManyThrough(Media::class, Member::class, secondKey: 'owner_id');
    }

    /**
     * All images uploaded by family members.
     */
    public function images(): HasManyThrough
    {
        return $this->media()->images();
    }

    /**
     * All videos uploaded by family members.
     */
    public function videos(): HasManyThrough
    {
        return $this->media()->videos();
    }

    public function addMember(Member|int $memberOrId): self
    {
        $member = model($memberOrId, Member::class);

        $this->members()->save($member);

        return $this;
    }

    public function join(Project|int $projectOrId): self
    {
        $project = model($projectOrId, Project::class);

        $this->members->each($project->addMember(...));

        $this->project()->associate($project)->save();

        return $this;
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }

    public function scopeWithMembers(Builder $query): void
    {
        $query->whereHas('members');
    }

    public function scopeSearch(Builder $query, string $q, bool $searchMembers = false): void
    {
        $query
            ->whereLike('name', "%$q%")
            ->when($searchMembers, fn (Builder $query) => $query->orWhereHas(
                'members',
                fn (Builder $query) => $query->whereRaw('CONCAT(firstname, " ", lastname) LIKE ?', "%$q%")
            ));
    }
}
