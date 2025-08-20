<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $activeUsers
 * @property-read int|null $active_users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $admins
 * @property-read int|null $admins_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Unit> $units
 * @property-read int|null $units_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @property-read Collection $families
 * @mixin \Eloquent
 */
class Project extends Model
{
    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Units (houses/apartments) defined in this habitational project.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    /**
     * All users associated with the project, regardless of their active status or role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('active')
            ->withTimestamps();
    }

    public function activeUsers(): BelongsToMany
    {
        return $this->users()->wherePivot('active', true);
    }

    public function members(): BelongsToMany
    {
        return $this->activeUsers()->whereIsAdmin(false);
    }

    public function admins(): BelongsToMany
    {
        return $this->activeUsers()->whereIsAdmin(true);
    }

    public function families(): Builder
    {
        return Family::whereIn('id', $this->members()->pluck('family_id'));
    }

    public function getFamiliesAttribute(): Collection
    {
        return $this->families()->with('members')->get();
    }

    public function scopeActive(Builder $builder): void
    {
        $builder->where('active', true);
    }

    public function scopeInactive(Builder $builder): void
    {
        $builder->where('active', false);
    }

    /**
     * Add a user to the project.
     */
    public function addUser(User $user): self
    {
        $this->users()->syncWithPivotValues($user, [ 'active' => true ], detaching: false);

        return $this;
    }

    /**
     * Remove a user from the project.
     */
    public function removeUser(User $user): self
    {
        $this->members()->updateExistingPivot($user, [ 'active' => false ]);

        return $this;
    }

    public function addFamily(Family $family): self
    {
        $family->join($this);

        return $this;
    }

    public function hasUser(User $user): bool
    {
        return $this->users()->where('users.id', $user->id)->exists();
    }

    public function hasMember(User $user): bool
    {
        return $user->isNotAdmin() && $this->hasUser($user);
    }

    public function hasAdmin(User $user): bool
    {
        return $user->isSuperAdmin() || ($user->isAdmin() && $this->hasUser($user));
    }
}
