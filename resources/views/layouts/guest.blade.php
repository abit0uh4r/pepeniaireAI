<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:600,700|manrope:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-950 antialiased">
        <div class="botanical-grid flex min-h-screen flex-col items-center justify-center bg-[#f4f0e6] px-4 py-10">
            <div class="text-center">
                <a href="/" class="inline-flex items-center gap-3 text-emerald-950">
                    <x-application-logo class="h-11 w-11" />
                    <span class="font-display text-2xl font-semibold">Pépinière IA</span>
                </a>
                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.22em] text-emerald-800">Espace gérant</p>
            </div>

            <div class="mt-7 w-full overflow-hidden rounded-[2rem] border border-emerald-950/10 bg-[#fffdf8] px-6 py-7 shadow-[0_24px_80px_-42px_rgba(6,78,59,0.45)] sm:max-w-md sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
