<?php

namespace App\Jobs;

use App\Models\Activity;
use App\Models\ErrorEntry;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * What only piles up goes: failed sign-ins (anyone can add them), errors that stopped
 * happening, jobs that failed long ago, expired cache rows (only read again do they go). The
 * rest of the activity log stays, it is the audit, but its IPs are erased once they are old.
 */
class PruneLogs implements ShouldQueue
{
    use Queueable;

    private const BATCH = 1000;

    public function handle(): void
    {
        $kept = config('limits.logs');

        $this->inBatches(Activity::where('action', 'sign_in_failed')->where('created_at', '<', now()->subDays($kept['failed_sign_ins_days'])));
        $this->eraseIps(now()->subDays($kept['ip_days']));
        $this->inBatches(ErrorEntry::where('last_seen_at', '<', now()->subDays($kept['errors_days'])));

        if (config('cache.default') === 'database') {
            $this->inBatches(DB::table(config('cache.stores.database.table'))->where('expiration', '<=', now()->timestamp));
        }

        Artisan::call('queue:prune-failed', ['--hours' => $kept['failed_jobs_days'] * 24]);
    }

    private function eraseIps(CarbonInterface $before): void
    {
        $old = Activity::whereNotNull('ip')->where('created_at', '<', $before);

        while ((clone $old)->limit(self::BATCH)->update(['ip' => null]) === self::BATCH);
    }

    /** A few at a time: nothing else waits long on these tables */
    private function inBatches(EloquentBuilder|Builder $rows): void
    {
        while ((clone $rows)->limit(self::BATCH)->delete() === self::BATCH);
    }
}
