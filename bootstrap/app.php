<?php

use App\Http\Middleware\EnsureInvitationAccepted;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleProjects;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

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
            HandleProjects::class,
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

        $middleware->alias([
            'invitation.accepted' => EnsureInvitationAccepted::class,
        ]);

        // $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
        // AuthenticationException::redirectUsing('login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
