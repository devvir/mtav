<?php

namespace App\Jobs;

use App\Models\OpenLottery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Open lotteries nobody touched for a long while go, with everything in them.
 */
class PurgeStaleOpenLotteries implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        OpenLottery::where('updated_at', '<', now()->subDays(config('limits.open_lottery.kept_days')))->delete();
    }
}
