<?php

namespace App\Policies;

use App\Models\Family;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function view(User $user, Project $project): bool
    {
        return $user->canAccess($project);
    }

    /**
     * Anyone with a verified account may start a project (within the limits for
     * unapproved projects).
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail()
            && Project::where('created_by', $user->id)->whereNull('approved_at')->count()
                < config('limits.unapproved_projects_per_user');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->manages($project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->manages($project);
    }

    /**
     * Admins invite into any family (or as admins); members, into their own family.
     */
    public function invite(User $user, Project $project, ?Family $family = null, bool $asAdmin = false): bool
    {
        if ($user->manages($project)) {
            return ! $family || $family->project_id === $project->id;
        }

        return ! $asAdmin && $family && $user->familyIn($project)?->is($family);
    }

    /**
     * Admins remove people from the project; anyone may leave it.
     */
    public function removeMember(User $user, Project $project, User $member): bool
    {
        return $user->manages($project) || $user->is($member);
    }

    public function rankUnits(User $user, Project $project): bool
    {
        return (bool) $user->familyIn($project);
    }

    public function runLottery(User $user, Project $project): bool
    {
        return $user->manages($project);
    }

    public function invalidateLottery(User $user, Project $project): bool
    {
        return $user->manages($project);
    }

    public function requestApproval(User $user, Project $project): bool
    {
        return $user->manages($project) && ! $project->isApproved();
    }

    /**
     * Superadmins only (they pass every check, see AppServiceProvider).
     */
    public function approve(User $user, Project $project): bool
    {
        return false;
    }
}
