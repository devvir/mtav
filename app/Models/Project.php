<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public static function current(): ?Project
    {
        return state('project');
    }

    public static function setCurrent(?Project $project = null): void
    {
        updateState('project', $project);
    }

    /**
     * Units (houses/apartments) defined in this habitational project.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
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
        return $this->activeUsers()->members();
    }

    public function admins(): BelongsToMany
    {
        return $this->activeUsers()->admins();
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }

    /**
     * Add a user to the project.
     */
    public function addUser(User|int $userOrId): self
    {
        $this->users()->syncWithPivotValues(
            model($userOrId, User::class),
            [ 'active' => true ],
            detaching: false
        );

        return $this;
    }

    /**
     * Remove a user from the project.
     */
    public function removeUser(User|int $userOrId): self
    {
        $this->members()->updateExistingPivot(
            model($userOrId, User::class),
            [ 'active' => false ]
        );

        return $this;
    }

    public function hasUser(User|int $userOrId): bool
    {
        $userId = $userOrId instanceof User ? $userOrId->id : $userOrId;

        return $this->users()->where('users.id', $userId)->exists();
    }

    public function hasMember(User|int $userOrId): bool
    {
        $user = model($userOrId, User::class);

        return $user->isNotAdmin() && $this->hasUser($user);
    }

    public function hasAdmin(User|int $userOrId): bool
    {
        $user = model($userOrId, User::class);

        return $user->isSuperAdmin() || ($user->isAdmin() && $this->hasUser($user));
    }

    public function scopeAlphabetically(Builder $query): void
    {
        $query->orderBy('name');
    }
}
