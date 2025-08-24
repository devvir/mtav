<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    public static function current(?Project $project = null): ?Project
    {
        return $project
            ? updateState('project', $project)
            : state('project');
    }

    /**
     * Units (houses/apartments) defined in this habitational project.
     */
    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function families(): Builder
    {
        return Family::whereIn('id', $this->members()->pluck('family_id'));
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

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
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
