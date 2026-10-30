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
    case INVITATION_RECEIVED = 'invitation_received';
    case ADDED_TO_FAMILY = 'added_to_family';
    case JOIN_REQUESTED = 'join_requested';
    case JOIN_ACCEPTED = 'join_accepted';
    case JOIN_DECLINED = 'join_declined';

    /** Lottery */
    case LOTTERY_COMPLETED = 'lottery_completed';
    case LOTTERY_FAILED = 'lottery_failed';
    case LOTTERY_INVALIDATED = 'lottery_invalidated';

    /** The forum */
    case FORUM_TOPIC = 'forum_topic';
    case FORUM_REPLY = 'forum_reply';

    /** Project approval */
    case APPROVAL_REQUESTED = 'approval_requested';
    case PROJECT_APPROVED = 'project_approved';
}
