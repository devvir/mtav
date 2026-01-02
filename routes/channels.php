<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Users may subscribe to their own private channel.
 */
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/**
 * Users may subscribe to the channel of any Project they have access to.
 */
Broadcast::channel('App.Models.Project.{id}', function (User $_, int $id) {
    return Project::whereId($id)->exists(); /** Uses Project Global Scope 'available' */
});
