<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @method static \Database\Factories\FamilyFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Family whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
            ->when($searchMembers, fn (Builder $query) => $query
                ->orWhereHas('members', fn (Builder $query) => $query
                    ->whereLike('email', "%$q%")
                    ->orWhereLike('phone', "%$q%")
                    ->orWhereLike('firstname', "%$q%")
                    ->orWhereLike('lastname', "%$q%")
            ));
    }
}
