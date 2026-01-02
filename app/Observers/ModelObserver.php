<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Base model observer that broadcasts resource events.
 *
 * It automatically observes all models that extend App\Models\Model.
 */
class ModelObserver
{
    public function __construct(
        private NotificationService $notifications,
    ) {
        // ...
    }

    /**
     * Handle the model "created" event.
     */
    public function created(Model $model): void
    {
        $this->notifications->handleResourceEvent($model, NotificationType::RESOURCE_CREATED);
    }

    /**
     * Handle the model "updated" event.
     */
    public function updated(Model $model): void
    {
        if (array_keys($model->getChanges()) === ['deleted_at']) {
            return; /** Ignore soft-delete and restore events */
        }

        $this->notifications->handleResourceEvent($model, NotificationType::RESOURCE_UPDATED);
    }

    /**
     * Handle the model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->notifications->handleResourceEvent($model, NotificationType::RESOURCE_DELETED);
    }

    /**
     * Handle the model "restored" event.
     */
    public function restored(Model $model): void
    {
        $this->notifications->handleResourceEvent($model, NotificationType::RESOURCE_RESTORED);
    }
}
