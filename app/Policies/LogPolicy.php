<?php

namespace App\Policies;

use App\Models\Log;
use App\Models\User;

class LogPolicy
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
    public function view(User $user, Log $log): bool
    {
        return $user->asAdmin()?->manages($log->project_id)
            || $user->asMember()?->family?->project_id === $log->project_id;
    }
}
