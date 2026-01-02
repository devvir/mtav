<?php

namespace App\Enums;

enum NotificationTarget: string
{
    /** For a specific User */
    case PRIVATE = 'private';

    /** For all Users in a Project */
    case PROJECT = 'project';

    /** For all multi-project Admins (incl. Superadmins) */
    case GLOBAL = 'global';
}
