<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- The person's appearance (cookies kept by the Appearance provider), applied before the first paint --}}
        <script>
            (function () {
                const cookie = (name) => document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))?.[1];
                const scheme = cookie('scheme') || 'auto';
                const dark = scheme === 'dark' || (scheme === 'auto' && matchMedia('(prefers-color-scheme: dark)').matches);

                document.documentElement.setAttribute('data-mantine-color-scheme', dark ? 'dark' : 'light');
                if (cookie('contrast') === 'high') document.documentElement.setAttribute('data-high-contrast', '');
                if (['small', 'large'].includes(cookie('text'))) document.documentElement.setAttribute('data-text-size', cookie('text'));
            })();
        </script>

        <title data-inertia>{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/images/app/apple-touch-icon.png">
        <link rel="manifest" href="/manifest.webmanifest">
        <meta name="theme-color" content="#faf7f4" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#120f0d" media="(prefers-color-scheme: dark)">

        @viteReactRefresh
        @vite('resources/js/app.tsx')
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
