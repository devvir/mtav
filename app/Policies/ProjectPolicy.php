<?php

namespace App\Policies;

use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() && $user->projects->count() > 1;
    }

    public function view(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Note: Superadmins can perform this action (they bypass Policies)
     */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
