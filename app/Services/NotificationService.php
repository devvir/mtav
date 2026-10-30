<?php

namespace App\Services;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Events\NotificationSent;
use App\Jobs\PushNotification;
use App\Services\Push\PushService;
use App\Models\Notification;
use App\Models\Project;
use App\Models\User;

/**
 * Creates in-app notifications and pushes them to whoever is online. A notification keeps
 * its words as translation keys and their values: each reader gets it in their language.
 */
class NotificationService
{
    /**
     * @param string $title, $message  Translation keys (a plain text is kept as it is)
     * @param array<string, string> $params  The values their placeholders take
     * @param string|null $url  What it is about: kept as a path in the app, so that it leads
     *                          there whatever address the app is reached by
     * @param int|null $targetId  The user (private) or project (project) addressed
     */
    public function send(
        NotificationTarget $target,
        NotificationType $type,
        string $title,
        string $message,
        array $params = [],
        ?string $url = null,
        ?User $trigger = null,
        ?int $targetId = null,
    ): Notification {
        $url = $url === null ? null : $this->path($url);

        $notification = Notification::create([
            'type'         => $type,
            'target'       => $target,
            'target_id'    => $target === NotificationTarget::GLOBAL ? null : $targetId,
            'data'         => compact('title', 'message', 'params', 'url'),
            'triggered_by' => $trigger?->id,
        ]);

        /** Live delivery is a bonus: a websocket server that is down never fails the action */
        rescue(fn () => NotificationSent::dispatch($notification));
        // And to people's devices, in the background
        PushService::enabled() && rescue(fn () => PushNotification::dispatch($notification->id));

        return $notification;
    }

    /**
     * Something a project gained, of which several tend to come together (setting up units,
     * sharing photos): the same person's within half an hour say so in one notification, as
     * unread again, that reaches people's devices only the first time.
     *
     * @param string $words  What its translation keys start with: {words}_title, _one, _many
     */
    public function bundle(Project $project, NotificationType $type, string $words, User $by, string $url, int $count = 1): Notification
    {
        $recent = Notification::where('target', NotificationTarget::PROJECT)
            ->where('target_id', $project->id)
            ->where('type', $type)
            ->where('triggered_by', $by->id)
            ->where('created_at', '>', now()->subMinutes(30))
            ->latest('id')
            ->first();

        if (! $recent) {
            return $this->send(
                NotificationTarget::PROJECT,
                $type,
                title: "notifications.{$words}_title",
                message: $count === 1 ? "notifications.{$words}_one" : "notifications.{$words}_many",
                params: ['name' => $by->name, 'count' => (string) $count],
                url: $url,
                trigger: $by,
                targetId: $project->id,
            );
        }

        $data = $recent->data;
        $data['params']['count'] = (string) ((int) $data['params']['count'] + $count);
        $data['message'] = "notifications.{$words}_many";
        $recent->update(['data' => $data]);
        $recent->readers()->detach();

        rescue(fn () => NotificationSent::dispatch($recent));

        return $recent;
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
