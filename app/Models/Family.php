<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    public function members(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function join(Project $project): self
    {
        $this->members->each($project->addUser(...));

        return $this;
    }

    public function scopeSearch(Builder $query, string $q, bool $searchMembers = false): void
    {
        $query
            ->whereLike('name', "%$q%")
            ->when(
                $searchMembers,
                fn (Builder $query) => $query->orWhereHas(
                    'members',
                    fn (Builder $query) => $query
                        ->whereLike('email', "%$q%")
                        ->orWhereLike('phone', "%$q%")
                        ->orWhereLike('firstname', "%$q%")
                        ->orWhereLike('lastname', "%$q%")
                )
            );
    }
}
