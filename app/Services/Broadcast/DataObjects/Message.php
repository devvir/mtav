<?php

// Copilot - Pending review

namespace App\Services\Broadcast\DataObjects;

use App\Services\Broadcast\Enums\BroadcastMessage;

/**
 * Data object representing a broadcastable message.
 *
 * Encapsulates the message type and payload for broadcasting.
 */
class Message
{
    /**
     * @param  BroadcastMessage  $type  The type of message being broadcast
     * @param  array<string, mixed>  $data  The data payload to send with the message
     * @param  array<string, mixed>  $metadata  Optional metadata about the message
     */
    public function __construct(
        public readonly BroadcastMessage $type,
        public readonly array $data = [],
        public readonly array $metadata = [],
    ) {
    }

    /**
     * Create a new message instance.
     */
    public static function make(
        BroadcastMessage $type,
        array $data = [],
        array $metadata = [],
    ): self {
        return new self($type, $data, $metadata);
    }

    /**
     * Get the full payload to broadcast.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'data' => $this->data,
            'metadata' => array_merge([
                'timestamp' => now()->toIso8601String(),
            ], $this->metadata),
        ];
    }

    /**
     * Add metadata to the message.
     */
    public function withMetadata(array $metadata): self
    {
        return new self(
            $this->type,
            $this->data,
            array_merge($this->metadata, $metadata),
        );
    }

    /**
     * Add data to the message.
     */
    public function withData(array $data): self
    {
        return new self(
            $this->type,
            array_merge($this->data, $data),
            $this->metadata,
        );
    }
}
