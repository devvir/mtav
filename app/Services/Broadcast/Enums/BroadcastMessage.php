<?php

// Copilot - Pending review

namespace App\Services\Broadcast\Enums;

/**
 * Enum for known broadcastable message types in the application.
 *
 * These represent different events/actions that can trigger broadcasts.
 */
enum BroadcastMessage: string
{
    case RESOURCE_CREATED = 'resource.created';
    case RESOURCE_UPDATED = 'resource.updated';
    case RESOURCE_DELETED = 'resource.deleted';

    case USER_JOINED = 'user.joined';
    case USER_LEFT = 'user.left';
    case USER_TYPING = 'user.typing';
    case USER_NAVIGATION = 'user.navigation';

    case NOTIFICATION = 'notification';
    case SYSTEM_MESSAGE = 'system.message';

    case LOTTERY_STARTED = 'lottery.started';
    case LOTTERY_COMPLETED = 'lottery.completed';

    /**
     * Get a descriptive label for the message type.
     */
    public function label(): string
    {
        return match ($this) {
            self::RESOURCE_CREATED  => 'Resource Created',
            self::RESOURCE_UPDATED  => 'Resource Updated',
            self::RESOURCE_DELETED  => 'Resource Deleted',
            self::USER_JOINED       => 'User Joined',
            self::USER_LEFT         => 'User Left',
            self::USER_TYPING       => 'User Typing',
            self::USER_NAVIGATION   => 'User Navigation',
            self::NOTIFICATION      => 'Notification',
            self::SYSTEM_MESSAGE    => 'System Message',
            self::LOTTERY_STARTED   => 'Lottery Started',
            self::LOTTERY_COMPLETED => 'Lottery Completed',
        };
    }
}
