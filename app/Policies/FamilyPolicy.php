<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Family $family): bool
    {
        return $user->isMember()
            ? $user->asMember()->family->project_id === $family->project_id
            : $user->asAdmin()->manages($family->project_id);
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
    public function update(User $user, Family $family): bool
    {
        return ($user->family_id === $family->id)
            || (bool) $user->asAdmin()?->manages($family->project_id);
    }

    public function delete(User $user, Family $family): bool
    {
        return (bool) $user->asAdmin()?->manages($family->project_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Family $family): bool
    {
        return (bool) $user->asAdmin()?->manages($family->project_id);
    }
}
