<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class AdminPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the authenticated User can view an Admin.
     *
     * IMPORTANT: To avoid N+1 queries when loading Admin collections with policies,
     * this method only performs partial validation. For Admins viewing other Admins,
     * the full constraint is enforced in AdminController@show via the ShowAdminRequest.
     *
     * - Users (Member or Admin) can view admins in their project
     * - Admins can only view admins with overlapping managed projects
     */
    public function view(User $user, Admin $admin): bool
    {
        return $user->isAdmin()
            || $user->asMember()?->project->admins->contains($admin);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Admin $admin): bool
    {
        return $user->is($admin);
    }

    /**
     * Determine whether the user can delete the admin.
     */
    public function delete(User $user, Admin $admin): bool
    {
        return $user->is($admin);
    }

    /**
     * Determine whether the user can restore the admin.
     */
    public function restore(User $user): bool
    {
        return false;
    }
}
