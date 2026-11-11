<?php

namespace App\Services\Push;

use App\Enums\NotificationTarget;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

/**
 * Notifications on people's devices, even with the app closed: to everyone a notification
 * is for (but whoever caused it), in their own language, on every device they allowed, all
 * sent together.
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
        $this->send($this->recipients($notification)->map(fn (User $user) => [$user, [
            ...$notification->text($user->locale ?? config('app.locale')),
            'url' => $notification->data['url'] ?? '/',
            'tag' => "notification-{$notification->id}",
        ]]));
    }

    /**
     * A chat message, to the others in its conversation: who wrote it (and in which group),
     * what they wrote. One per conversation on a device: a newer replaces the one before.
     */
    public function deliverMessage(Message $message): void
    {
        $conversation = $message->conversation;

        // Everyone in the project: on people's devices it would never stop
        if ($conversation->is_community) {
            return;
        }

        $author = $message->author?->name ?? '';
        // Only those who may read it now: not whoever left the project, nor a blocked account
        $others = $conversation->audience()->whereKeyNot($message->user_id)
            ->whereHas('pushSubscriptions')->with('pushSubscriptions')->get();

        // A group with no name goes by its people, as it does in the chat
        $people = $conversation->is_group && ! $conversation->title
            ? $conversation->participants()->pluck('firstname', 'users.id')
            : collect();

        $this->send($others->map(function (User $user) use ($conversation, $message, $people, $author) {
            $where = $conversation->title ?: $people->except([$message->user_id, $user->id])->take(3)->join(', ');

            return [$user, [
                'title'   => collect([$author, $conversation->is_group ? $where : null])->filter()->join(' · '),
                'message' => Str::limit($message->body, 140),
                'url'     => route('chat.show', [$conversation->project_id, $conversation]),
                'tag'     => "conversation-{$conversation->id}",
            ]];
        }));
    }

    /**
     * To every device of each person, all sent together. One device's trouble stops nobody
     * else's notification, nor repeats it to those who had it; a device its browser dropped
     * is forgotten.
     *
     * @param Collection<int, array{0: User, 1: array{title: string, message: string, url: string, tag: string}}> $deliveries
     */
    private function send(Collection $deliveries): void
    {
        foreach ($deliveries as [$user, $payload]) {
            foreach ($user->pushSubscriptions as $subscription) {
                try {
                    $this->sender->queue($subscription, $payload);
                } catch (Throwable $error) {
                    report($error);
                }
            }
        }

        foreach ($this->sender->flush() as $dropped) {
            $dropped->delete();
        }
    }

    /**
     * Everyone it is for (Notification::for decides), on a device, but whoever caused it: they
     * see it in the bell, and their devices are not told what they just did
     *
     * @return Collection<int, User>
     */
    private function recipients(Notification $notification): Collection
    {
        $id = $notification->target_id;

        // Who it could be for, by whom it is addressed to
        $addressed = match ($notification->target) {
            NotificationTarget::PRIVATE => User::whereKey($id),
            NotificationTarget::PROJECT => User::whereHas('memberships', fn ($q) => $q->where('project_id', $id)),
            NotificationTarget::GLOBAL  => User::where('is_superadmin', true),
        };

        return $addressed
            ->when($notification->triggered_by, fn ($q, $trigger) => $q->whereKeyNot($trigger))
            ->whereHas('pushSubscriptions')
            ->with('pushSubscriptions', 'memberships')
            ->get()
            ->filter(fn (User $user) => $notification->isFor($user))
            ->values();
    }
}
