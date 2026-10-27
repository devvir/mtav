<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;

class InvitationPolicy
{
    /**
     * Admins revoke any pending invitation of their project; others, their own.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return $invitation->invited_by === $user->id || $user->manages($invitation->project_id);
    }
}
