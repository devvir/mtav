<?php

namespace App\Services\Push;

use App\Enums\NotificationTarget;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Notifications on people's devices, even with the app closed: to everyone a notification
 * is for (but whoever caused it), in their own language, on every device they allowed.
 */
class PushService
{
    public function __construct(private PushSender $sender)
    {
    }

    /** The deployment has its keys (`php artisan push:keys`) */
    public static function enabled(): bool
    {
        return filled(config('services.push.public_key')) && filled(config('services.push.private_key'));
    }

    public function deliver(Notification $notification): void
    {
        foreach ($this->recipients($notification) as $user) {
            $payload = [
                ...$notification->text($user->locale ?? config('app.locale')),
                'url' => $notification->data['url'] ?? '/',
                'tag' => "notification-{$notification->id}",
            ];

            foreach ($user->pushSubscriptions as $subscription) {
                $this->sender->send($subscription, $payload) || $subscription->delete();
            }
        }
    }

    /** @return Collection<int, User> */
    private function recipients(Notification $notification): Collection
    {
        $id = $notification->target_id;

        $audience = match ($notification->target) {
            NotificationTarget::PRIVATE => User::whereKey($id),
            NotificationTarget::PROJECT => User::whereHas('memberships', fn ($q) => $q->where('project_id', $id)),
            NotificationTarget::GLOBAL  => User::where('is_superadmin', true),
        };

        return $audience
            ->when($notification->triggered_by, fn ($q, $trigger) => $q->whereKeyNot($trigger))
            ->whereHas('pushSubscriptions')
            ->with('pushSubscriptions')
            ->get();
    }
}
