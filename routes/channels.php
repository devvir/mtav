<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Users with more than one Project may subscribe to the global channel.
 */
Broadcast::channel('global', function (User $user) {
    return $user->asAdmin()?->projects()->count() > 1;
});

/**
 * Users may subscribe to the channel of any Project they have access to.
 * This is a presence channel, so it returns user data.
 */
Broadcast::channel('projects.{project}', function (User $user, Project $project) {
    return [
        'id'   => $user->id,
        'name' => $user->fullname,
    ];
});

/**
 * Users may subscribe to their own private channel.
 */
Broadcast::channel('private.{user}', function (User $authUser, User $user) {
    return $user->is($authUser);
});
