<?php

use App\Events\MessageSent;
use App\Events\NotificationSent;
use App\Events\OpenLotteryChanged;
use App\Events\ProjectChanged;
use App\Jobs\AnswerInChat;
use App\Jobs\PushMessage;
use App\Jobs\PushNotification;
use App\Models\Message;
use App\Models\Notification;

/**
 * What reaches people at once waits behind nothing long: live updates and devices have their
 * queue, the Assistant its own, and every server runs a worker for each.
 */

it('sends live updates apart from what takes long', function ($event) {
    expect($event->broadcastQueue())->toBe('realtime');
})->with([
    'a message'           => fn () => new MessageSent(new Message(), []),
    'a notification'      => fn () => new NotificationSent(new Notification()),
    'a project\'s change' => fn () => new ProjectChanged(1, 'families', 'created', 1),
    'an open lottery'     => fn () => new OpenLotteryChanged('slug'),
]);

it('sends to devices apart from what takes long, and answers apart from them both', function () {
    expect((new PushNotification(1))->queue)->toBe('realtime')
        ->and((new PushMessage(1))->queue)->toBe('realtime')
        ->and((new AnswerInChat(1))->queue)->toBe('assistant');
});

describe('On a server', function () {
    // Each service with the command that works a queue: its name, and the queue it names
    $workers = function () {
        preg_match_all('/^  ([\w-]+):\n(?:(?!^  \S).*\n)*?\s+command: (.*queue:work.*)$/m', file_get_contents(base_path('deploy/slot.yml')), $found, PREG_SET_ORDER);

        return collect($found)->mapWithKeys(fn ($service) => [
            $service[1] => preg_match("/--queue=([\w,]+)/", $service[2], $queue) ? $queue[1] : 'default',
        ]);
    };

    it('runs one worker per queue', function () use ($workers) {
        expect($workers()->sort()->values()->all())->toBe(['assistant', 'default', 'realtime']);
    });

    it('stops every worker of the slot it leaves, going forward and going back', function () {
        // Taken down whole, the slot's every service stops: its workers among them, none left out
        $downs = collect(file(base_path('deploy/host/deploy')))
            ->filter(fn ($line) => str_contains($line, 'slot_compose "$live_slot" "$live_image" down'));

        expect($downs)->toHaveCount(2)
            ->and($downs->every(fn ($line) => trim(\Illuminate\Support\Str::after($line, 'down')) === ''))->toBeTrue();
    });
});

describe('A worker without its database', function () {
    $refused = fn () => new Illuminate\Database\QueryException('mariadb', 'select * from `cache`', [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));

    it('is the steady worker the app builds', function () {
        expect(app('queue.worker'))->toBeInstanceOf(App\Queue\SteadyWorker::class);
    });

    it('waits for the database before starting, pausing longer each time, saying so after each minute', function () {
        Illuminate\Support\Facades\Log::spy();
        $worker = app('queue.worker');
        $answers = [false, false, false, false, false, false, false, false, false, false, true];
        $worker->databaseAnswers = function () use (&$answers) {
            return array_shift($answers);
        };
        $pauses = [];
        $worker->pause = function (int $seconds) use (&$pauses) {
            $pauses[] = $seconds;
        };

        $worker->waitForTheDatabase();

        expect($pauses)->toBe([1, 2, 4, 8, 10, 10, 10, 10, 10, 10]);
        // 75 seconds: a word once the first minute is gone, none for a blip
        Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->once()->with('The worker waits for the database (65s so far)');
        Illuminate\Support\Facades\Log::shouldHaveReceived('info')->once()->withArgs(fn ($line) => str_contains($line, 'after 75s'));
    });

    it('starts at once when the database answers, saying nothing', function () {
        Illuminate\Support\Facades\Log::spy();
        $worker = app('queue.worker');
        $worker->databaseAnswers = fn () => true;
        $worker->pause = fn () => throw new RuntimeException('never paused');

        $worker->waitForTheDatabase();

        Illuminate\Support\Facades\Log::shouldNotHaveReceived('warning');
    });

    it('waits for the database lost between jobs, and carries on rather than stopping', function () use ($refused) {
        $worker = app('queue.worker');
        $cache = Mockery::mock(Illuminate\Contracts\Cache\Repository::class);
        // Read, then refused while the database is gone, then read again once it is back
        $reads = [1700000000, $refused(), 1700000000];
        $cache->shouldReceive('get')->with('illuminate:queue:restart')->andReturnUsing(function () use (&$reads) {
            $read = array_shift($reads);

            return $read instanceof Throwable ? throw $read : $read;
        });
        $worker->setCache($cache);
        $read = fn () => (fn () => $this->getTimestampOfLastQueueRestart())->call($worker);

        expect($read())->toBe(1700000000)->and($worker->lostConnection)->toBeFalse();
        // The database gone: the signal as last read stands, and the worker takes it up at its next check
        expect($read())->toBe(1700000000)->and($worker->lostConnection)->toBeTrue();

        // There, it waits until the database answers, and goes on
        $answers = [false, false, true];
        $worker->databaseAnswers = function () use (&$answers) {
            return array_shift($answers);
        };
        $worker->pause = fn () => null;
        // (no memory limit: the whole suite's process is past the worker's own)
        $options = new Illuminate\Queue\WorkerOptions(memory: PHP_INT_MAX);
        $stop = (fn () => $this->stopIfNecessary($options, 1700000000))->call($worker);
        expect($stop)->toBeNull()->and($worker->lostConnection)->toBeFalse()->and($answers)->toBe([])->and($reads)->toBe([]);
    });

    it('pauses a round whose listeners lost the database, rather than failing', function () use ($refused) {
        $worker = app('queue.worker');
        Illuminate\Support\Facades\Event::listen(Illuminate\Queue\Events\Looping::class, fn () => throw $refused());

        $runs = (fn () => $this->daemonShouldRun(new Illuminate\Queue\WorkerOptions(), 'database', 'default'))->call($worker);

        expect($runs)->toBeFalse()->and($worker->lostConnection)->toBeTrue();
    });

    it('stops at once when asked to while it waits', function () {
        $worker = app('queue.worker');
        $worker->databaseAnswers = fn () => false;
        $worker->pause = function () use ($worker) {
            $worker->shouldQuit = true;
        };
        $worker->lostConnection = true;

        $stop = (fn () => $this->stopIfNecessary(new Illuminate\Queue\WorkerOptions(), null))->call($worker);
        expect($stop[1])->toBe(Illuminate\Queue\WorkerStopReason::LostConnection);
    });

    it('still fails on what is no lost database', function () {
        $worker = app('queue.worker');
        $cache = Mockery::mock(Illuminate\Contracts\Cache\Repository::class);
        $cache->shouldReceive('get')->andThrow(new RuntimeException('something else'));
        $worker->setCache($cache);

        expect(fn () => (fn () => $this->getTimestampOfLastQueueRestart())->call($worker))->toThrow(RuntimeException::class, 'something else');
    });

    it('reports a lost database as no error, and everything else as ever', function () use ($refused) {
        $handler = Mockery::mock(Illuminate\Contracts\Debug\ExceptionHandler::class);
        $quiet = new App\Queue\QuietAboutLostConnections($handler);
        $other = new RuntimeException('a real failure');
        $handler->shouldReceive('report')->once()->with($other);
        $handler->shouldReceive('shouldReport')->with($other)->andReturn(true);

        $quiet->report($refused());
        $quiet->report($other);

        expect($quiet->shouldReport($refused()))->toBeFalse()
            ->and($quiet->shouldReport($other))->toBeTrue();
    });
});
