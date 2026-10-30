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

    /**
     * From the directory, into a project open to anyone not in it yet.
     */
    public function join(User $user, Project $project): bool
    {
        return $project->isInDirectory() && $project->open_to_join && $project->acceptsNewcomers() && ! $user->canAccess($project);
    }

    /**
     * From the directory, where its admins decide; once, until they answer, and again a
     * while after they decline.
     */
    public function askToJoin(User $user, Project $project): bool
    {
        if (! $project->isInDirectory() || $project->open_to_join || ! $project->acceptsNewcomers() || $user->canAccess($project)) {
            return false;
        }

        $request = $project->joinRequests()->firstWhere('user_id', $user->id);

        return ! $request || (! $request->isPending() && ! $request->isSettled());
    }

    /**
     * Someone taking part with no family, and no other role, starts theirs (until the
     * lottery decided who gets what, and once there are unit types to choose from).
     */
    public function createOwnFamily(User $user, Project $project): bool
    {
        $membership = $user->membershipIn($project);

        return $membership && ! $membership->family_id && ! $membership->is_admin && ! $project->lotteryLocked();
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
