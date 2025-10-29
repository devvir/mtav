<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the authenticated User can view a Member.
     *
     * IMPORTANT: To avoid N+1 queries when loading Member collections with policies,
     * this method only performs partial validation. For Users viewing Members, the full
     * constraint is enforced in MemberController@show via the ShowMemberRequest.
     *
     * - Users (Member or Admin) can view Members in projects they manage
     */
    public function view(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the authenticated User can update a Member.
     *
     * IMPORTANT: To avoid N+1 queries when loading Member collections with policies,
     * this method only performs partial validation. For Admins updating Members, the
     * full constraint is enforced in MemberController@update via the UpdateMemberRequest.
     *
     * - Users (Member or Admin) can update themselves
     * - Admins can only update members in projects they manage
     */
    public function update(User $user, Member $member): bool
    {
        return $user->is($member) || $user->isAdmin();
    }

    /**
     * Determine whether the authenticated User can delete a Member.
     *
     * IMPORTANT: To avoid N+1 queries when loading Member collections with policies,
     * this method only performs partial validation. For Admins deleting Members, the
     * full constraint is enforced in MemberController@destroy via the DeleteMemberRequest.
     *
     * - Users (Member or Admin) can delete themselves
     * - Admins can only delete members in projects they manage
     */
    public function delete(User $user, Member $member): bool
    {
        return $user->is($member) || $user->isAdmin();
    }

    /**
     * Determine whether the authenticated User can restore a Member.
     *
     * IMPORTANT: To avoid N+1 queries when loading Member collections with policies,
     * this method only performs partial validation. For Admins restoring Members, the
     * full constraint is enforced in MemberController@restore via the RestoreMemberRequest.
     *
     * - Admins can only restore members in projects they manage
     */
    public function restore(User $user): bool
    {
        return $user->isAdmin();
    }
}
