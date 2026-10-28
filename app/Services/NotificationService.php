<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;

/**
 * Creates in-app notifications and pushes them to whoever is online.
 */
class NotificationService
{
    /**
     * @param string|null $url  What it is about: kept as a path in the app, so that it leads
     *                          there whatever address the app is reached by
     * @param int|null $targetId  The user (private) or project (project) addressed
     */
    public function send(
        NotificationTarget $target,
        NotificationType $type,
        string $title,
        string $message,
        ?string $url = null,
        ?User $trigger = null,
        ?int $targetId = null,
    ): Notification {
        $url = $url === null ? null : $this->path($url);

        $notification = Notification::create([
            'type'         => $type,
            'target'       => $target,
            'target_id'    => $target === NotificationTarget::GLOBAL ? null : $targetId,
            'data'         => compact('title', 'message', 'url'),
            'triggered_by' => $trigger?->id,
        ]);

        /** Live delivery is a bonus: a websocket server that is down never fails the action */
        rescue(fn () => NotificationSent::dispatch($notification));

        return $notification;
    }

    /** A URL's path, query and fragment: '/projects/1/events?x=1#y' */
    private function path(string $url): string
    {
        $parts = parse_url($url);

        return ($parts['path'] ?? '/')
            . (isset($parts['query']) ? "?{$parts['query']}" : '')
            . (isset($parts['fragment']) ? "#{$parts['fragment']}" : '');
    }
}
