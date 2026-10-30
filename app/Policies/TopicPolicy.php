<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A project's forum is everyone's in the project: anyone in it reads, opens topics and
 * replies (in an open topic). Admins pin, close and remove topics.
 */
class TopicPolicy extends ProjectResourcePolicy
{
    public function create(User $user, Project $project): bool
    {
        return $user->canAccess($project);
    }

    /** @param Topic $model */
    public function reply(User $user, Model $model): bool
    {
        return $user->canAccess($model->project_id) && (! $model->is_closed || $user->manages($model->project_id));
    }
}
