<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Member;
use App\Models\User;

class UserPolicy
{
    /**
     * Call the right policy (MemberPolicy or AdminPolicy) instead.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Call the right policy (MemberPolicy or AdminPolicy) instead.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('view', $this->concreteUserType($model));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('update', $this->concreteUserType($model));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('delete', $this->concreteUserType($model));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('restore', $this->concreteUserType($model));
    }

    /**
     * Policies apply to either Members or Admins, never to generic Users.
     */
    protected function concreteUserType(User $model): Member|Admin
    {
        return $model->isAdmin() ? $model->asAdmin() : $model->asMember();
    }
}
