<?php

// Copilot - Pending review

namespace App\Services\Broadcast\Enums;

/**
 * Enum for available broadcast channels in the application.
 *
 * These channels determine where messages are sent in the WebSocket infrastructure.
 */
enum BroadcastChannel: string
{
    /**
     * Private channel for a specific user.
     * Format: private.{userId}
     */
    case PRIVATE = 'private';

    /**
     * Presence channel for a specific project.
     * Format: projects.{projectId}
     * Users in the project can see who else is online.
     */
    case PROJECT = 'projects';

    /**
     * Global channel for multi-project admins.
     * Only accessible to users with multiple projects.
     */
    case GLOBAL = 'global';

    /**
     * Get the full channel name with identifier(s).
     *
     * @param  int|string|null  $identifier  The channel identifier (user ID, project ID, etc.)
     * @return string The full channel name
     */
    public function channel(int|string|null $identifier = null): string
    {
        return match ($this) {
            self::PRIVATE => "private.{$identifier}",
            self::PROJECT => "projects.{$identifier}",
            self::GLOBAL => 'global',
        };
    }

    /**
     * Whether this channel requires an identifier.
     */
    public function requiresIdentifier(): bool
    {
        return $this !== self::GLOBAL;
    }
}
