<?php

namespace App\Policies;

use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        /** There is no Plans index */
        return false;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(): bool
    {
        /** Plans are created by the system */
        return false;
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }

    public function delete(): bool
    {
        /** Plans cannot be deleted */
        return false;
    }

    public function restore(): bool
    {
        /** Plans cannot be deleted */
        return false;
    }
}
