<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Cache;

/**
 * Whether anything works the queue: each worker leaves a mark as it loops (at most every
 * half minute), and none fresh means none runs. What waits on the queue (a lottery run)
 * is then held, and said, rather than left waiting for good.
 */
class Workers
{
    private const MARK = 'queue.worker-seen';

    /** A worker unseen this long is taken for stopped (it marks every 30 s at most) */
    private const STALE_SECONDS = 120;

    /** Called as a worker loops: cheap, a write at most every half minute */
    public static function mark(): void
    {
        if (now()->timestamp - (int) Cache::get(self::MARK, 0) >= 30) {
            Cache::put(self::MARK, now()->timestamp, self::STALE_SECONDS * 5);
        }
    }

    /** Whether something works the queue (always, where jobs run in the request itself) */
    public static function running(): bool
    {
        if (in_array(config('queue.default'), ['sync', 'deferred', 'null'], true)) {
            return true;
        }

        return now()->timestamp - (int) Cache::get(self::MARK, 0) < self::STALE_SECONDS;
    }
}
