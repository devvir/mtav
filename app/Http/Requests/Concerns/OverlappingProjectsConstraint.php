<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;

trait OverlappingProjectsConstraint
{
    /**
     * Make sure the authenticated User and the User relevant to this request
     * have overlapping Projects.
     *
     * Members' projects are exactly zero or one (the active one, potentially none)
     * Admins' projects are the projects the Admin manages (0+)
     *
     * NOTE: this checks belong, strictly speaking, to a Policy. However, due to
     * the nature of the relationship (User <-> Project is many to many), there is
     * no efficient way to check the relation for a Collection of Users at once, as
     * is required from Policies (due to the auto-loading of policies on Resource
     * collections). The decision was to do a best-attempt in the policy to restrict
     * access, while leaving the checks that would trigger N+1 queries to this trait.
     */
    protected function validateOverlap(User $authUser, User $user, ?string $error = null): void
    {
        if ($authUser->isSuperadmin() || $authUser->is($user)) {
            return;
        }

        $overlappingProjects = $authUser->projects->find(
            $user->projects()->pluck('projects.id')
        );

        if ($overlappingProjects->isEmpty()) {
            abort(403, $error ?? __('You are not allowed to perform this action this project.'));
        }
    }
}