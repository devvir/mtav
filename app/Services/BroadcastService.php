<?php

// Copilot - Pending review

namespace App\Services;

use App\Services\Broadcast\DataObjects\Message;
use App\Services\Broadcast\Enums\BroadcastChannel;
use Exception;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast as BroadcastFacade;

/**
 * Service for broadcasting messages via WebSockets (Laravel Reverb).
 *
 * Provides a clean, simple API for broadcasting messages to channels
 * without consumers needing to know implementation details.
 *
 * Usage:
 *   app(BroadcastService::class)->send(
 *       BroadcastChannel::PROJECT,
 *       Message::make(BroadcastMessage::RESOURCE_CREATED, ['id' => 123]),
 *       projectId: 1
 *   );
 */
class BroadcastService
{
    /**
     * Send a message to a broadcast channel.
     *
     * @param  BroadcastChannel  $channel  The channel to broadcast to
     * @param  Message  $message  The message to broadcast
     * @param  int|string|null  $identifier  Channel identifier (user ID, project ID, etc.)
     * @return void
     *
     * @throws Exception If channel requires identifier but none provided
     */
    public function send(
        BroadcastChannel $channel,
        Message $message,
        int|string|null $identifier = null,
    ): void {
        // Validate that identifier is provided if required
        if ($channel->requiresIdentifier() && $identifier === null) {
            throw new Exception("Channel {$channel->value} requires an identifier");
        }

        $channelName = $channel->channel($identifier);
        $channelInstance = $this->createChannel($channelName, $channel);

        // Broadcast to the channel using Laravel's AnonymousEvent
        BroadcastFacade::on($channelInstance)
            ->via($this->getConnection())
            ->as($this->getEventName($message))
            ->with($message->toArray())
            ->send();
    }

    /**
     * Send a message to a user's private channel.
     *
     * @param  int  $userId  The user ID
     * @param  Message  $message  The message to broadcast
     */
    public function toUser(int $userId, Message $message): void
    {
        $this->send(BroadcastChannel::PRIVATE, $message, $userId);
    }

    /**
     * Send a message to a project channel.
     *
     * @param  int  $projectId  The project ID
     * @param  Message  $message  The message to broadcast
     */
    public function toProject(int $projectId, Message $message): void
    {
        $this->send(BroadcastChannel::PROJECT, $message, $projectId);
    }

    /**
     * Send a message to the global channel (multi-project admins).
     *
     * @param  Message  $message  The message to broadcast
     */
    public function toGlobal(Message $message): void
    {
        $this->send(BroadcastChannel::GLOBAL, $message);
    }

    /**
     * Get the broadcast connection name.
     */
    protected function getConnection(): string
    {
        return config('broadcasting.default', 'reverb');
    }

    /**
     * Create the appropriate channel instance.
     */
    protected function createChannel(string $channelName, BroadcastChannel $channelType): PrivateChannel|PresenceChannel
    {
        // Project channels are presence channels (support user tracking)
        if ($channelType === BroadcastChannel::PROJECT) {
            return new PresenceChannel($channelName);
        }

        // Private and global channels are private channels
        return new PrivateChannel($channelName);
    }

    /**
     * Get the event name for the message.
     */
    protected function getEventName(Message $message): string
    {
        return $message->type->value;
    }
}
