<?php

namespace App\Models\Concerns;

use App\Services\ActivityLog;

/**
 * A model whose every creation, change and deletion is written to the activity log
 * (ActivityLog), with what changed
 */
trait RecordsActivity
{
    /** Listeners that return nothing: a false would stop the model's other listeners */
    public static function bootRecordsActivity(): void
    {
        static::created(function ($model) {
            ActivityLog::record('created', $model);
        });
        static::updated(function ($model) {
            if ($changes = ActivityLog::changes($model)) {
                ActivityLog::record('updated', $model, $changes);
            }
        });
        static::deleted(function ($model) {
            ActivityLog::record('deleted', $model);
        });
    }
}
