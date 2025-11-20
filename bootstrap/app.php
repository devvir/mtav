<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleInvitedUsers;
use App\Http\Middleware\HandleSelectedProject;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
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
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state', 'project']);

        $middleware->web(append: [
            HandleInvitedUsers::class,
            HandleSelectedProject::class,
            HandleAppearance::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleInertiaRequests::class,
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
