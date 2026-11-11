<?php

namespace App\Services\Lottery;

use App\Enums\LotteryRunStatus;
use App\Jobs\PublishLotteryRun;
use App\Jobs\RunLottery;
use App\Models\LotteryRun;
use App\Models\PlatformSetting;
use App\Services\Lottery\DataObjects\ExecutionResult;
use App\Services\Lottery\Enums\LotteryAuditType;
use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A run's life, the same whatever the lottery belongs to: started with what it decides from
 * recorded, solved in the background, held until its reveal, then completed, or failed. Each
 * host (`LotteryHost`) says what a run decides from and what to do with its result.
 */
class LotteryRuns
{
    public function __construct(private AuditService $audits)
    {
    }

    /**
     * Start a run, then solve it in the background. In one transaction: `$admit` holds the host
     * still and refuses a run it may not start (by throwing), the run is written as running, and
     * its manifest is recorded (`init`): what is solved is what was checked.
     *
     * @param HasMany<LotteryRun> $runs  the host's runs
     * @param array<string, mixed> $attributes  the host's own (who started it, what they confirmed)
     * @param array<string, mixed> $init  recorded with the manifest (who started it)
     */
    public function start(HasMany $runs, Closure $admit, array $attributes = [], array $init = []): LotteryRun
    {
        $run = DB::transaction(function () use ($runs, $admit, $attributes, $init) {
            $admit();

            $run = $runs->create([
                'uuid'       => (string) Str::uuid(),
                'status'     => LotteryRunStatus::RUNNING,
                'solver'     => config('lottery.default'),
                'started_at' => now(),
                // People see it take a while: a lottery over in an instant feels like nothing
                'reveal_at' => now()->addSeconds((int) PlatformSetting::value(PlatformSetting::LOTTERY_SECONDS)),
                ...$attributes,
            ]);

            $this->audits->record($run->id, LotteryAuditType::INIT, [...$init, 'manifest' => $run->host()->manifest($run)]);

            return $run;
        });

        RunLottery::dispatch($run);

        return $run;
    }

    /** The assignment found: written now if the run is due to end, else held until it is (until then everyone sees the lottery running) */
    public function solved(LotteryRun $run, ExecutionResult $result): void
    {
        if (! $run->reveal_at || $run->reveal_at->isPast()) {
            $this->complete($run, $result);

            return;
        }

        $run->update(['result' => ['picks' => $result->picks, 'orphans' => $result->orphans]]);
        PublishLotteryRun::dispatch($run)->delay($run->reveal_at);
    }

    /** Write the assignment, all at once, and close the run; the result held is let go */
    public function complete(LotteryRun $run, ExecutionResult $result): void
    {
        $host = $run->host();

        DB::transaction(function () use ($run, $result, $host) {
            $host->assign($run, $result);

            $run->update(['status' => LotteryRunStatus::COMPLETED, 'finished_at' => now(), 'result' => null]);
        });

        $host->completed($run);
    }

    /**
     * Close a run that could not finish; it decided nothing, so the lottery can run again. A run
     * that already finished stays as it is (what failed came after it). The reason is kept as
     * words to say, for each viewer in their language.
     *
     * @param array<string, mixed> $debug  recorded with the failure, for whoever looks into it
     */
    public function fail(LotteryRun $run, string $messageKey, array $messageParams = [], array $debug = []): void
    {
        $closed = DB::transaction(function () use ($run, $messageKey, $messageParams, $debug) {
            if (LotteryRun::whereKey($run->id)->lockForUpdate()->value('status') !== LotteryRunStatus::RUNNING) {
                return false;
            }

            $run->update([
                'status'      => LotteryRunStatus::FAILED,
                'finished_at' => now(),
                'failure'     => LotteryRun::failureOf($messageKey, $messageParams),
            ]);

            $this->audits->record($run->id, LotteryAuditType::FAILURE, ['message' => __($messageKey, $messageParams), ...$debug]);

            return true;
        });

        if ($closed) {
            $run->host()->failed($run);
        }
    }
}
