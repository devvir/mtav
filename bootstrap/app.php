<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(
            at: [
                '127.0.0.0/8',    // localhost
                '10.0.0.0/8',     // private network
                '172.16.0.0/12',  // Docker networks
                '192.168.0.0/16', // private network
            ],
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Inertia visits get a friendly error page (or, for a refused form submission,
         * a flash message on the page they came from) instead of the framework's.
         */
        $exceptions->respond(function (Response $response, Throwable $_, Request $request) {
            $status = $response->getStatusCode();

            if (! $request->header('X-Inertia') || ! in_array($status, [403, 404, 500, 503])) {
                return $response;
            }

            if ($status === 403 && ! $request->isMethod('GET')) {
                return back()->with('error', __('flash.not_allowed'));
            }

            if ($status === 500 && app()->hasDebugModeEnabled()) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })
    ->create();
