<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- The person's mode and theme (cookies set by useTheme), applied before the first paint --}}
        <script>
            (function () {
                const cookie = (name) => document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'))?.[1];
                const mode = cookie('mode') || 'system';
                const theme = cookie('theme') || 'default';
                const dark = mode === 'dark' || (mode === 'system' && matchMedia('(prefers-color-scheme: dark)').matches);

                document.documentElement.classList.toggle('dark', dark);
                if (theme !== 'default') document.documentElement.classList.add('theme-' + theme);
            })();
        </script>

        <title data-inertia>{{ config('app.name') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="@container/body font-sans antialiased">
        @inertia
    </body>
</html>
