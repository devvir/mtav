<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Member $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Member $model): bool
    {
        return $user->isAdmin() || $user->is($model);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Member $model): bool
    {
        return $user->isAdmin() || $user->is($model);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Member $model): bool
    {
        return $user->isAdmin() && $model->isSoftDeletable() && $model->deleted_at;
    }
}
