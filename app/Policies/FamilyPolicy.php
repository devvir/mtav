<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\User;

class FamilyPolicy extends ProjectResourcePolicy
{
    /** Anyone taking part in its project */
    public function askToJoin(User $user, Family $family): bool
    {
        return (bool) $user->membershipIn($family->project_id);
    }

    /** Any member of it, and only they: whether someone belongs in a family is for the family to say */
    public function answerRequests(User $user, Family $family): bool
    {
        return $family->memberships()->where('user_id', $user->id)->exists();
    }
}
