<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

/** A post is changed by whoever wrote it, and removed by them or the project's admins */
class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $post->author_id === $user->id && $user->canAccess($post->project_id);
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->author_id === $user->id || $user->manages($post->project_id);
    }
}
