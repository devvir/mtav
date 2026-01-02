<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Services\Notifications\ResourceLifecycle;
use App\Services\Notifications\SystemNotifications;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    public function __construct(
        protected ResourceLifecycle $resources,
        protected SystemNotifications $system,
    ) {
        // ...
    }

    /**
     * Handle resource lifecycle events.
     */
    public function handleResourceEvent(Model $model, NotificationType $type): void
    {
        $this->resources->notify($model, $type);
    }
}
