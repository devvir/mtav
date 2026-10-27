<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Something in a project was added, changed or removed: pages open on it refresh
 * what they show. Carries no data beyond what changed (pages reload their own).
 */
class ProjectChanged implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    /**
     * @param 'created'|'updated'|'deleted' $action
     */
    public function __construct(
        public int $projectId,
        public string $resource,
        public string $action,
        public int $id,
        public ?int $by = null,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel("projects.{$this->projectId}")];
    }

    public function broadcastAs(): string
    {
        return 'changed';
    }

    public function broadcastWith(): array
    {
        return [
            'resource' => $this->resource,
            'action'   => $this->action,
            'id'       => $this->id,
            'by'       => $this->by,
        ];
    }
}
