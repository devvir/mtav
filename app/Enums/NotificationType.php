<?php

namespace App\Enums;

enum NotificationType: string
{
    /** Something was added, changed or removed in a project */
    case RESOURCE_CREATED = 'resource_created';
    case RESOURCE_UPDATED = 'resource_updated';
    case RESOURCE_DELETED = 'resource_deleted';

    /** People */
    case MEMBER_JOINED = 'member_joined';

    /** Lottery */
    case LOTTERY_COMPLETED = 'lottery_completed';
    case LOTTERY_FAILED = 'lottery_failed';
    case LOTTERY_INVALIDATED = 'lottery_invalidated';

    /** Project approval */
    case APPROVAL_REQUESTED = 'approval_requested';
    case PROJECT_APPROVED = 'project_approved';
}
