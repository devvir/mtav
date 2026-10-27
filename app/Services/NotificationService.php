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
}
