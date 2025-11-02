<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Member $member): bool
    {
        return $user->isAdmin() || $user->is($member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->isAdmin() || $user->is($member);
    }

    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
