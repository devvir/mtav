<?php

namespace App\Enums;

enum NotificationTarget: string
{
    /** For one user */
    case PRIVATE = 'private';

    /** For everyone in a project */
    case PROJECT = 'project';

    /** For the platform's superadmins */
    case GLOBAL = 'global';
}
