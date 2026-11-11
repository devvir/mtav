<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * A project's forum is everyone's in the project: anyone in it reads, opens topics and
 * replies (in an open topic). Admins pin, close, rename and remove topics; whoever opened one
 * renames it, and removes it while nobody has replied.
 */
class TopicPolicy extends ProjectResourcePolicy
{
    public function create(User $user, Project $project): bool
    {
        return $user->canAccess($project);
    }

    /** @param Topic $model */
    public function rename(User $user, Model $model): bool
    {
        return $this->update($user, $model) || $model->author_id === $user->id;
    }

    /** @param Topic $model  As it is now: a reply arriving at the same moment keeps it */
    public function remove(User $user, Model $model): bool
    {
        return $this->delete($user, $model) || ($model->author_id === $user->id && ! $model->replies_count);
    }

    /** @param Topic $model */
    public function reply(User $user, Model $model): bool
    {
        return $user->canAccess($model->project_id) && (! $model->is_closed || $user->manages($model->project_id));
    }
}
