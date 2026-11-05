<?php

namespace App\Queue;

use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The queue worker, steady when the database is not there (it restarts, a deploy brings it up beside the worker):
 *
 * - it starts only once the database answers, waiting quietly meanwhile, rather than failing and being restarted
 *   in a loop;
 * - the database lost while it runs (fetching a job, checking for a restart signal, or in what listens to each
 *   round, which read the database too), it waits for it the same way and carries on, connecting again: Laravel's
 *   worker would stop, to be restarted, or fail outright on the other two;
 * - asked to stop meanwhile (a deploy), it stops at once;
 * - a lost database is reported as no error (QuietAboutLostConnections): it says nothing a person must act on.
 */
class SteadyWorker extends Worker
{
    /** The longest pause between two tries, in seconds; after how long, and how often, it says it still waits */
    private const LONGEST_PAUSE = 10;

    private const SAYS_EVERY = 60;

    /** The restart signal as last read: what stands while the database cannot be asked */
    private mixed $knownRestart = null;

    /** @var (callable(): bool)|null  whether the database answers (tests give their own) */
    public $databaseAnswers = null;

    /** @var (callable(int): void)|null  a pause of so many seconds (tests give their own) */
    public $pause = null;

    /** Laravel's worker, made steady: the same queues, events and the rest, its errors quiet about lost connections */
    public static function from(Worker $worker): self
    {
        $steady = new self(
            $worker->manager,
            $worker->events,
            new QuietAboutLostConnections($worker->exceptions),
            $worker->isDownForMaintenance,
            $worker->resetScope,
        );

        return $steady;
    }

    public function daemon($connectionName, $queue, WorkerOptions $options)
    {
        $this->waitForTheDatabase();

        return parent::daemon($connectionName, $queue, $options);
    }

    /**
     * Until the database answers (or the worker is asked to stop): tries again after a pause that grows to 10
     * seconds, saying so after each minute of waiting (a blip, a database restarting, is no news)
     */
    public function waitForTheDatabase(): void
    {
        $answers = $this->databaseAnswers ?? fn () => $this->databaseAnswers();
        $pause = $this->pause ?? fn (int $seconds) => sleep($seconds);
        $waited = 0;
        $said = 0;
        $next = 1;

        while (! $this->shouldQuit && ! $answers()) {
            if ($waited - $said >= self::SAYS_EVERY) {
                Log::warning("The worker waits for the database ({$waited}s so far)");
                $said = $waited;
            }
            $pause($next);
            $waited += $next;
            $next = min($next * 2, self::LONGEST_PAUSE);
        }

        if ($waited) {
            Log::info("The database answered after {$waited}s: the worker carries on");
        }
    }

    protected function getTimestampOfLastQueueRestart()
    {
        try {
            return $this->knownRestart = parent::getTimestampOfLastQueueRestart();
        } catch (Throwable $e) {
            if (! $this->causedByLostConnection($e)) {
                throw $e;
            }
            // Taken up at the next check (stopIfNecessary): it waits for the database there, and carries on
            $this->lostConnection = true;

            return $this->knownRestart;
        }
    }

    /**
     * Each round's first check: whether to run at all (maintenance, a paused queue) and what listens to the round
     * (the heartbeat Workers::mark leaves, in the database). The database lost there, it pauses this round and
     * waits for it at the next check, as anywhere else.
     */
    protected function daemonShouldRun(WorkerOptions $options, $connectionName, $queue)
    {
        try {
            return parent::daemonShouldRun($options, $connectionName, $queue);
        } catch (Throwable $e) {
            if (! $this->causedByLostConnection($e)) {
                throw $e;
            }
            $this->lostConnection = true;

            return false;
        }
    }

    /** The database lost: waited for, connected again, and the worker goes on rather than stopping */
    protected function stopIfNecessary(WorkerOptions $options, $lastRestart, $startTime = 0, $job = null)
    {
        if ($this->lostConnection) {
            $this->waitForTheDatabase();
            $this->lostConnection = $this->shouldQuit;
        }

        return parent::stopIfNecessary($options, $lastRestart, $startTime, $job);
    }

    private function databaseAnswers(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            DB::purge();

            return false;
        }
    }
}
