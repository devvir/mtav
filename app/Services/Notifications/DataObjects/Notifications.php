<?php

namespace App\Services\Notifications\DataObjects;

use App\Services\Notifications\NotificationCollection;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class Notifications implements Arrayable, JsonSerializable
{
    public function __construct(
        private NotificationCollection $notifications,
        private bool $hasMore,
    ) {
        // ...
    }

    public function list(): NotificationCollection
    {
        return $this->notifications;
    }

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    public function toArray(): array
    {
        return [
            'data'    => $this->notifications,
            'hasMore' => $this->hasMore,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
