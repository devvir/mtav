<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Users may subscribe to their own private channel.
 */
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/**
 * Users may subscribe to their currently selected project's channel.
 */
Broadcast::channel('App.Models.Project.{id}', function (User $user, int $id) {
    return $user->isSuperAdmin || state('project')?->id === $id;
});
