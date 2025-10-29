<?php

namespace App\Policies;

use App\Models\UnitType;
use App\Models\User;

class UnitTypePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, UnitType $unitType): bool
    {
        return (bool) $user->isAdmin()
            ? $user->asAdmin()->manages($unitType->project_id)
            : (bool) $user->asMember()->project?->is($unitType->project_id);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, UnitType $unitType): bool
    {
        return (bool) $user->asAdmin()?->manages($unitType->project_id);
    }

    public function delete(User $user, UnitType $unitType): bool
    {
        return (bool) $user->asAdmin()?->manages($unitType->project_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UnitType $unitType): bool
    {
        return (bool) $user->asAdmin()?->manages($unitType->project_id);
    }
}
