<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic event for when any resource is created.
 *
 * This event broadcasts resource creation to the frontend,
 * providing a consistent interface for all model types.
 */
class ResourceCreated extends RealTimeNotification
{
    /**
     * Create a new event instance.
     */
    public function __construct(public Model $model)
    {
        // ...
    }

    /**
     * Get the user-facing notification message.
     */
    public function message(): string
    {
        $modelName = class_basename($this->model);

        return __('events.resource_created', [
            'resource' => __("models.{$modelName}"),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channelName = str(class_basename($this->model))->plural()->lower();

        return [
            new Channel($channelName),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ResourceCreated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'base_class' => class_basename($this->model),
            'id' => $this->model->getKey(),
            'message' => $this->message(),
            // Note: Full model data removed for security.
            // Frontend should fetch the resource via API if needed.
        ];
    }
}
