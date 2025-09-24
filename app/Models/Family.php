<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addMember(User|int $userOrId): self
    {
        $user = model($userOrId, User::class);

        $this->members()->save($user);

        return $this;
    }

    public function join(Project|int $projectOrId): self
    {
        $project = model($projectOrId, Project::class);

        $this->members->each($project->addUser(...));

        $this->project()->associate($project)->save();

        return $this;
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
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
