<?php

use App\Services\ErrorLog;
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
        // Where a request's time goes, in DevTools (local development only)
        if (env('APP_ENV') === 'local') {
            $middleware->prepend(\App\Http\Middleware\ServerTiming::class);
        }

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
            \App\Http\Middleware\SetLocale::class,
            AddLinkHeadersForPreloadedAssets::class,
            HandleInertiaRequests::class,
            \App\Http\Middleware\RequireOwner::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('home'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Kept for superadmins to see (the console's Errors), the owner told of new ones
        $exceptions->report(fn (Throwable $error) => ErrorLog::capture($error));

        /**
         * People get a friendly error page (or, for a refused form submission, a flash
         * message on the page they came from) instead of the framework's.
         */
        $exceptions->respond(function (Response $response, Throwable $_, Request $request) {
            $status = $response->getStatusCode();

            // More than a request may carry (a share of too many large files): said on the page
            if ($status === 413 && ! $request->expectsJson()) {
                return back()->with('error', __('flash.upload_too_large'));
            }

            // A page left open too long (its form's token expired): back to it, with a fresh
            // token, to try again
            if ($status === 419 && ! $request->expectsJson()) {
                return back()->with('error', __('flash.page_expired'));
            }

            if ($request->expectsJson() || ! in_array($status, [403, 404, 500, 503])) {
                return $response;
            }

            if ($status === 403 && ! $request->isMethod('GET')) {
                return back()->with('error', __('flash.not_allowed'));
            }

            if ($status === 500 && app()->hasDebugModeEnabled()) {
                return $response;
            }

            // Refused before the shared data was added (a model not found in the address): the
            // page still gets it, so it shows within the app the person is signed in to
            // (a request with no session, such as a file's, gets the bare page)
            if ($request->hasSession()) {
                Inertia::share(app(HandleInertiaRequests::class)->share($request));
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })
    ->create();
