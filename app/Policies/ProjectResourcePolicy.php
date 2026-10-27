<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Things that belong to a project and that its admins configure: everyone in the
 * project sees them, admins change them. Creation checks receive the project.
 */
abstract class ProjectResourcePolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->canAccess($project);
    }

    public function view(User $user, Model $model): bool
    {
        return $user->canAccess($model->project_id);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->manages($project);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->manages($model->project_id);
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->manages($model->project_id);
    }
}
