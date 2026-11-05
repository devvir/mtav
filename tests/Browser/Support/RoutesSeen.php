<?php

namespace Tests\Browser\Support;

use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\Event;

/**
 * Every route the browser tests make the app serve, one line each (method, name, address), appended to
 * storage/logs/e2e-routes.log. The pipeline keeps it from each run, so what no journey ever reaches shows by
 * comparing it with every route the app has.
 */
class RoutesSeen
{
    public static function record(): void
    {
        Event::listen(RouteMatched::class, function (RouteMatched $matched) {
            file_put_contents(
                storage_path('logs/e2e-routes.log'),
                "{$matched->request->method()} {$matched->route->getName()} {$matched->route->uri()}\n",
                FILE_APPEND | LOCK_EX,
            );
        });
    }
}
