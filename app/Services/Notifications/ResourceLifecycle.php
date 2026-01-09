<?php

namespace App\Services\Notifications;

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Event;
use App\Models\Family;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Handles resource lifecycle notifications.
 *
 * All events handled here are relative to a given Project and will be broadcast on the
 * specific Project channel, except for Project create, delete and restore (global). In
 * particular, no event handled here is private (i.e. meant for user private channels).
 */
class ResourceLifecycle
{
    protected const HANDLED_RESOURCES = [
        Event::class,
        Family::class,
        Media::class,
        Project::class,
        Unit::class,
        UnitType::class,
    ];

    /**
     * Create a Notification for the given resource lifecycle event.
     */
    public function notify(Model $model, NotificationType $type): void
    {
        if ($this->dontNotify($model, $type)) {
            return;
        }

        $isGlobal = $model instanceof Project && $type !== NotificationType::RESOURCE_UPDATED;

        Notification::create([
            'type'         => $type,
            'target'       => $isGlobal ? NotificationTarget::GLOBAL : NotificationTarget::PROJECT,
            'target_id'    => $isGlobal ? null : ($model instanceof Project ? $model->id : $model->project_id),
            'data'         => $this->notificationData($model, $type),
            'triggered_by' => Auth::id(),
        ]);
    }

    /**
     * Notification data payload.
     */
    private function notificationData(Model $model, NotificationType $type): array
    {
        $event = $this->type2event($type);

        return [
            'resource'    => strtolower(class_basename($model)),
            'resource_id' => $model->id,
            'title'       => $this->notificationTitle($model, $event),
            'message'     => $this->notificationMessage($model, $event),
            'action'      => $this->notificationAction($model),
        ];
    }

    /**
     * @param  'created'|'updated'|'deleted'|'restored'  $event
     */
    private function notificationTitle(Model $model, string $event): string
    {
        /** @var 'project'|'event'|'family'|'unit'|'unittype'|'media' $resource */
        $resource = strtolower(class_basename($model));
        $translationKey = "events.resource_{$resource}_{$event}_title";

        return __($translationKey, [ 'creator' => Auth::user()?->name ?? config('app.name') ]);
    }

    /**
     * @param  'created'|'updated'|'deleted'|'restored'  $event
     */
    private function notificationMessage(Model $model, string $event): string
    {
        /** @var 'project'|'event'|'family'|'unit'|'unittype'|'media' $resource */
        $resource = strtolower(class_basename($model));
        $translationKey = "events.resource_{$resource}_{$event}_message";

        $translationParams = match (get_class($model)) {
            Event::class    => [ 'title' => $model->title ],
            Family::class   => [ 'name' => $model->name ],
            Media::class    => [ 'description' => Str::limit($model->description, 30, preserveWords: true) ],
            Project::class  => [ 'name' => $model->name ],
            Unit::class     => [ 'identifier' => $model->identifier, 'type' => $model->type->name ],
            UnitType::class => [ 'name' => $model->name ],
        };

        return __($translationKey, $translationParams);
    }

    /**
     * Route to be used as action link of the Notification.
     */
    private function notificationAction(Model $model): ?string
    {
        /** @var 'projects'|'events'|'families'|'units'|'unit_types'|'medium' $resourceNS */
        $resourceNS = Str::snake(Str::plural(class_basename($model)));

        return match (get_class($model)) {
            Project::class => route('projects.index'),
            Media::class   => $model->isVisual()
                ? route('gallery')
                : ($model->isAudio() ? route('audios.index') : route('documents.index')),

            default => route("{$resourceNS}.show", $model),
        };
    }

    /**
     * @return 'created'|'updated'|'deleted'|'restored'  $event
     */
    private function type2event(NotificationType $type): string
    {
        return match ($type) {
            NotificationType::RESOURCE_CREATED  => 'created',
            NotificationType::RESOURCE_UPDATED  => 'updated',
            NotificationType::RESOURCE_DELETED  => 'deleted',
            NotificationType::RESOURCE_RESTORED => 'restored',
        };
    }

    /**
     * Notify only events of handled resources, excluding Lottery created event (system-managed).
     */
    private function dontNotify(Model $model, NotificationType $type)
    {
        return ! in_array(get_class($model), self::HANDLED_RESOURCES)
            || ($model instanceof Event && $model->isLottery() && $type === NotificationType::RESOURCE_CREATED);
    }
}
