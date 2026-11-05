<?php

namespace App\Queue;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\DetectsLostConnections;
use Throwable;

/**
 * The worker's error handler: the app's own, but a lost database is no error to report. The worker stops and waits
 * for it (SteadyWorker); reporting it would only mail the owner about an outage that mends itself.
 */
class QuietAboutLostConnections implements ExceptionHandler
{
    use DetectsLostConnections;

    public function __construct(private ExceptionHandler $handler)
    {
    }

    public function report(Throwable $e)
    {
        if (! $this->causedByLostConnection($e)) {
            $this->handler->report($e);
        }
    }

    public function shouldReport(Throwable $e)
    {
        return ! $this->causedByLostConnection($e) && $this->handler->shouldReport($e);
    }

    public function render($request, Throwable $e)
    {
        return $this->handler->render($request, $e);
    }

    public function renderForConsole($output, Throwable $e)
    {
        $this->handler->renderForConsole($output, $e);
    }
}
