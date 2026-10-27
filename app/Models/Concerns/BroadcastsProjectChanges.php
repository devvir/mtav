<?php

namespace App\Models\Concerns;

use App\Events\ProjectChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Announce every change of a project's model on the project's channel, once the
 * surrounding transaction (if any) commits.
 *
 * The resource name is the model's snake-cased plural (families, unit_types, …), or
 * $broadcastAs when set.
 */
trait BroadcastsProjectChanges
{
    public static function bootBroadcastsProjectChanges(): void
    {
        foreach (['created', 'updated', 'deleted'] as $action) {
            static::$action(fn ($model) => $model->announce($action));
        }
    }

    protected function announce(string $action): void
    {
        $projectId = $this->project_id;
        $resource = property_exists($this, 'broadcastAs')
            ? $this->broadcastAs
            : Str::snake(Str::plural(class_basename($this)));
        $id = $this->getKey();
        $by = Auth::id();

        /** Live updates are a bonus: a websocket server that is down never fails the action */
        DB::afterCommit(fn () => rescue(fn () => ProjectChanged::dispatch($projectId, $resource, $action, $id, $by)));
    }
}
