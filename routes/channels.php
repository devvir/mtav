<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/**
 * Each person's own notifications.
 */
Broadcast::channel('users.{id}', fn (User $user, int $id) => $user->id === $id);

/**
 * What happens in a project, for the people taking part in it.
 */
Broadcast::channel('projects.{id}', fn (User $user, int $id) => $user->canAccess($id));

/**
 * Platform-wide notices (new projects, approval requests) for superadmins.
 */
Broadcast::channel('superadmins', fn (User $user) => $user->isSuperadmin());
