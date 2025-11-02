<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\User;

class AdminPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Admin $admin): bool
    {
        return $user->is($admin);
    }

    public function delete(User $user, Admin $admin): bool
    {
        return $user->is($admin);
    }

    /**
     * Note: Superadmins can perform this action (they bypass Policies)
     */
    public function restore(User $user): bool
    {
        return false;
    }
}
