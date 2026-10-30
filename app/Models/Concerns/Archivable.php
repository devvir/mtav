<?php

namespace App\Models\Concerns;

use App\Services\ActivityLog;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Something whose removal keeps it, hidden, for its admins to see and restore: who did what
 * stays traceable. What nobody came to rely on (worthKeeping) simply goes.
 */
trait Archivable
{
    use SoftDeletes;

    /** How long a mistake is just a mistake, with nothing else having happened to it */
    public const MISTAKE_MINUTES = 60;

    abstract public function worthKeeping(): bool;

    public static function bootArchivable(): void
    {
        static::restored(function ($model) {
            ActivityLog::record('restored', $model);
            method_exists($model, 'announce') && $model->announce('created');
        });
    }

    public function retire(): void
    {
        $this->worthKeeping() ? $this->delete() : $this->forceDelete();
    }

    protected function isFresh(): bool
    {
        return $this->created_at->isAfter(now()->subMinutes(self::MISTAKE_MINUTES));
    }
}
