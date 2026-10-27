<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Everyone in a project shares files; a file's owner edits its description, and
 * the owner or an admin removes it.
 */
class MediaPolicy extends ProjectResourcePolicy
{
    public function create(User $user, Project $project): bool
    {
        return (bool) $user->membershipIn($project);
    }

    /**
     * @param Media $model
     */
    public function update(User $user, Model $model): bool
    {
        return $model->owner_id === $user->id;
    }

    /**
     * @param Media $model
     */
    public function delete(User $user, Model $model): bool
    {
        return $model->owner_id === $user->id || $user->manages($model->project_id);
    }
}
