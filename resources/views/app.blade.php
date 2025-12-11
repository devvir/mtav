<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect dark mode preference and apply it immediately --}}
        <script>
            (function() {
                // Parse cookies to find mode value
                const cookies = document.cookie.split('; ').reduce((acc, cookie) => {
                    const [name, value] = cookie.split('=');
                    acc[decodeURIComponent(name)] = decodeURIComponent(value);
                    return acc;
                }, {});

                const mode = cookies.mode || 'system';
                  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (mode === 'dark' || (mode === 'system' && prefersDark)) {
                  document.documentElement.classList.add('dark');
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(14.1% 0.005 285.823);
            }

            @if (env('APP_DEBUG') && request()->has('b'))
                * {
                    border: 1px solid rgba(255, 0, 0, 0.2);
                    outline: 1px solid rgba(255, 0, 0, 0.2);
                }
            @endif
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="@container/body font-sans antialiased">
        @inertia
    </body>
</html>
