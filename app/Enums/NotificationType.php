<?php

namespace App\Enums;

enum NotificationType: string
{
    /** Resource lifecycle events */
    case RESOURCE_CREATED = 'resource_created';
    case RESOURCE_UPDATED = 'resource_updated';
    case RESOURCE_DELETED = 'resource_deleted';
    case RESOURCE_RESTORED = 'resource_restored';

    /** User/Member events */
    case REGISTRATION_CONFIRMED = 'registration_confirmed';

    /** Unit/Housing events */
    case UNIT_ASSIGNED = 'unit_assigned';
    case LOTTERY_COMPLETED = 'lottery_completed';

    /** Event/RSVP events */
    case RSVP_CONFIRMED = 'rsvp_confirmed';
    case EVENT_REMINDER = 'event_reminder';

    /** Project/Construction events */
    case CONSTRUCTION_UPDATE = 'construction_update';
    case MILESTONE_REACHED = 'milestone_reached';

    /** General/Admin events */
    case NEWS_POSTED = 'news_posted';
    case SYSTEM_ANNOUNCEMENT = 'system_announcement';
    case SYSTEM_MAINTENANCE = 'system_maintenance';
}
