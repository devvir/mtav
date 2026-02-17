<?php

use App\Http\Middleware\BroadcastNavigationTest;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleInvitedUsers;
use App\Http\Middleware\HandleSelectedProject;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
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

        $middleware->encryptCookies(except: ['project']);

        $middleware->web(append: [
            HandleInvitedUsers::class,
            HandleSelectedProject::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleInertiaRequests::class,
            BroadcastNavigationTest::class, // TEMPORARY TEST - Remove after testing
        ]);

        $middleware->api(append: [ 'web' ]);
        $middleware->statefulApi();

        $middleware->validateCsrfTokens(except: [
            'login',
            'logout',
            'csrf-token',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /** For Policy-based denials on navigation, silently redirect to the Dashboard */
        $exceptions->render(fn (AccessDeniedHttpException $_) => redirect()->route('dashboard'));
    })
    ->create();
